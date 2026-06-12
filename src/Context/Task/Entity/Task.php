<?php

declare(strict_types=1);

namespace App\Context\Task\Entity;

use App\Context\Task\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

/**
 * Reference aggregate (ADR-0012): entities live at context level so multiple
 * feature slices (CreateTask, ListTasks, CompleteTask) act on one model.
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\Column(length: 255)]
    public private(set) string $title;

    #[ORM\Column(nullable: true)]
    public private(set) ?DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    public private(set) DateTimeImmutable $createdAt;

    /** Virtual (unmapped) — hooks are for get-only computed properties. */
    public bool $completed {
        get => $this->completedAt !== null;
    }

    public function __construct(string $title)
    {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->createdAt = new DateTimeImmutable;
    }

    /** Idempotent: completing a completed task keeps the original timestamp. */
    public function complete(): void
    {
        $this->completedAt ??= new DateTimeImmutable;
    }
}
