<?php

declare(strict_types=1);

namespace App\Task\Entity;

use App\Board\Entity\Column;
use App\User\Entity\User;
use App\Task\Enums\TaskStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'uuid', unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', enumType: TaskStatus::class)]
    private TaskStatus $status;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Column::class)]
    #[ORM\JoinColumn(name: 'column_id', nullable: false)]
    private Column $column;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assignee_id')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false)]
    private User $owner;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 10)]
    private string $position = '0';

    #[ORM\Column(type: 'jsonb', options: ['jsonb' => true])]
    private array $metadata = [];

    #[ORM\Column(name: 'due_date', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'completed_at', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(
        string $title,
        Column $column,
        User $owner,
        ?string $description = null
    ) {
        $this->uuid = uuid_create();
        $this->title = $title;
        $this->column = $column;
        $this->owner = $owner;
        $this->description = $description;
        $this->status = TaskStatus::Backlog;
        $this->position = '0';
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        if ($status === TaskStatus::Done) {
            $this->completedAt = new \DateTimeImmutable();
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getColumn(): Column
    {
        return $this->column;
    }

    public function setColumn(Column $column): void
    {
        $this->column = $column;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): void
    {
        $this->assignee = $assignee;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): void
    {
        $this->dueDate = $dueDate;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function move(TaskStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Invalid status transition from {$this->status->value} to {$newStatus->value}"
            );
        }
        $this->setStatus($newStatus);
    }
}
