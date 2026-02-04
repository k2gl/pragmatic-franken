# Mercure Integration

Real-time updates for the Kanban board using Mercure.

## Configuration

```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            jwt: '%env(MERCURE_JWT)%'
```

## Environment Variables

```env
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT=your-jwt-token
```

## Publishing Events

```php
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class TaskNotifier
{
    public function __construct(private ?HubInterface $hub = null) {}
    
    public function notifyTaskCreated(Task $task): void
    {
        if ($this->hub === null) {
            return;
        }
        
        $update = new Update(
            "https://your-domain.com/board/{$task->getColumn()->getBoard()->getId()}",
            json_encode([
                'event' => 'task_created',
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'columnId' => $task->getColumn()->getId(),
                ]
            ])
        );
        
        $this->hub->publish($update);
    }
}
```

## Client-Side Subscription

```javascript
import { EventSource } from 'mercure-js';

const eventSource = new EventSource('https://your-domain.com/.well-known/mercure?topic=https://your-domain.com/board/1');

eventSource.addEventListener('task_created', (event) => {
    const data = JSON.parse(event.data);
    console.log('New task:', data.task);
});
```

## Available Events

| Event | Description |
|-------|-------------|
| `task_created` | New task was created |
| `task_updated` | Task was modified |
| `task_moved` | Task moved to different column |
| `task_deleted` | Task was removed |
| `column_created` | New column added |
| `column_reordered` | Columns reordered |

## Topic Patterns

- `/board/{id}` - All events for a specific board
- `/board/{id}/task/{taskId}` - Events for specific task
- `/user/{userId}` - User-specific notifications
