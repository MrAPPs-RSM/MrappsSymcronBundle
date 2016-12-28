<?php

namespace Mrapps\SymCronBundle\Controller;

use Mrapps\SymCronBundle\Services\Client;
use Mrapps\SymCronBundle\Services\CronManager;
use Mrapps\SymCronBundle\Services\GroupSelector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client as GuzzleClient;

/**
 * @Route("/cronjob")
 */
class CronjobController
    extends Controller
{
    /**
     * @Route("/next", name="cron_next")
     */
    public function nextAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $groups = $entityManager
            ->getRepository("MrappsSymCronBundle:GroupTask")
            ->findAllActiveGroups();

        $groupSelector = new GroupSelector();
        $groupSelector->addGroups($groups);

        $cronManager = new CronManager($groupSelector, $entityManager);
        $nextTask = $cronManager->nextActivity();

        if ($nextTask == null) {
            return new JsonResponse(array(
                "success" => false,
                "message" => "All tasks are running or completed",
            ));
        }

        $nextTask->setStarted();
        $entityManager->persist($nextTask);
        $entityManager->flush();

        $client = new Client(
            new GuzzleClient(),
            $this->container->get("logger"),
            $request->getSchemeAndHttpHost()
        );

        $resultTask = $client->runTask($nextTask);

        if ($resultTask == null) {
            return new JsonResponse(array(
                "success" => false,
                "message" => "Failed to call task id " . $nextTask->getId(),
            ));
        }

        if (!$resultTask->isStarted()) {
            $success = true;
            $message = 'Task with id '.$resultTask->getId().' has already started';
        } else {
            $success = $resultTask != null && $resultTask->isSuccess();
            if ($success && $resultTask->getGroup()->isCompleted()) {
                $resultTask->getGroup()->incrementIterationCounter();
            }
            $message = $success
            ? null
            : "Error running task id " . $resultTask->getId();
        }

        $entityManager->persist($resultTask->getGroup());
        $entityManager->persist($resultTask);
        $entityManager->flush();        

        return new JsonResponse(array(
            "success" => $success,
            "message" => $message
        ));
    }
}
