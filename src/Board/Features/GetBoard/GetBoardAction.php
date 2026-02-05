<?php

namespace App\Board\Features\GetBoard;

use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Boards")]
final class GetBoardAction extends AbstractController
{
    #[Route('/api/boards/{id}', name: 'get_board', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Full board structure with columns and tasks",
        content: new OA\JsonContent(ref: "#/components/schemas/BoardResponse")
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 404, description: "Board not found")]
    public function __invoke(
        int $id,
        MessageBusInterface $bus
    ): BoardResponse {
        $user = $this->getUser();
        assert($user instanceof User);
        $query = new GetBoardQuery($id);
        $query->setUser($user);
        $envelope = $bus->dispatch($query);
        return $envelope->last(HandledStamp::class)->getResult();
    }
}
