<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\Task\Entity\Task;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private string $email;

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(name: 'github_id', unique: true, nullable: true)]
    private ?int $githubId = null;

    #[ORM\Column(name: 'github_username', nullable: true)]
    private ?string $githubUsername = null;

    #[ORM\Column(name: 'avatar_url', nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'last_login_at', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(string $email, ?string $password = null, ?string $name = null)
    {
        $this->email = $email;
        $this->password = $password ?? '';
        $this->name = $name ?? $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGithubId(): ?int
    {
        return $this->githubId;
    }

    public function setGithubId(?int $githubId): void
    {
        $this->githubId = $githubId;
    }

    public function getGithubUsername(): ?string
    {
        return $this->githubUsername;
    }

    public function setGithubUsername(?string $githubUsername): void
    {
        $this->githubUsername = $githubUsername;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function updateLastLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getBoards(): Collection
    {
        return $this->tasks;
    }
}
