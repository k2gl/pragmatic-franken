<?php

declare(strict_types=1);

namespace App\Context\Task\Entity;

use App\Context\Task\Repository\TaskRepository;
use App\Context\Task\Shared\TaskTitle;
use App\Context\Task\Shared\TaskTitleType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

/**
 * Reference aggregate (ADR-0012): entities live at context level so multiple
 * feature slices (CreateTask, ListTasks, CompleteTask) act on one model.
 *
 * Created only through Task::create() — the private constructor plus the
 * TaskTitle value object keep the aggregate self-validating on every path,
 * not only behind the HTTP #[Assert] edge (ADR-0018).
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public private(set) Uuid $id;

    #[ORM\Column(type: TaskTitleType::NAME, length: TaskTitle::MAX_LENGTH)]
    public private(set) TaskTitle $title;

    #[ORM\Column(nullable: true)]
    public private(set) ?DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    public private(set) DateTimeImmutable $createdAt;

    /** Virtual (unmapped) — hooks are for get-only computed properties. */
    public bool $completed {
        get => $this->completedAt !== null;
    }

    private function __construct(TaskTitle $title)
    {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->createdAt = new DateTimeImmutable;
    }

    /** Named constructor (ADR-0018): the only way in — guarantees a valid title on every path. */
    public static function create(string $title): self
    {
        return new self(new TaskTitle($title));
    }

    /** Idempotent: completing a completed task keeps the original timestamp. */
    public function complete(): void
    {
        $this->completedAt ??= new DateTimeImmutable;
    }
}
