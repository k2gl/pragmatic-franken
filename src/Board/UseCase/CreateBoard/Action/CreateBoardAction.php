<?php

declare(strict_types=1);

namespace App\Board\UseCase\CreateBoard\Action;

use App\Board\UseCase\CreateBoard\Input\CreateBoardMessage;
use App\Board\UseCase\CreateBoard\Output\BoardCreatedResponse;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Boards")]
final class CreateBoardAction extends AbstractController
{
    #[Route('/api/boards', name: 'create_board', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/CreateBoardMessage")
    )]
    #[OA\Response(
        response: 201,
        description: "Board created successfully",
        content: new OA\JsonContent(ref: "#/components/schemas/BoardCreatedResponse")
    )]
    #[OA\Response(response: 401, description: "Unauthorized")]
    #[OA\Response(response: 422, description: "Validation error")]
    public function __invoke(
        CreateBoardMessage $message,
        MessageBusInterface $bus
    ): JsonResponse {
        $user = $this->getUser();
        assert($user instanceof User);
        $message->setUser($user);
        
        $response = $bus->dispatch($message);
        
        return new JsonResponse(
            $response,
            JsonResponse::HTTP_CREATED
        );
    }
}
