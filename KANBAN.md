# Kanban Board Implementation

## Overview

This document describes the Kanban board implementation for the Symfony + FrankenPHP enterprise stack.

## Features

- **Column Management**: Create, update, delete board columns
- **Task Management**: Create, move, reorder tasks
- **Drag & Drop**: Fractional indexing for smooth task reordering
- **Real-time Updates**: Mercure integration for live board updates
- **API-First**: All operations available via REST API

## Database Schema

### Boards Table
- `id`: Primary key
- `title`: Board title
- `owner_id`: Foreign key to User
- `uuid`: Unique identifier
- `settings`: JSON configuration
- `is_active`: Board status

### Board Columns Table
- `id`: Primary key
- `board_id`: Foreign key to Board
- `name`: Column name
- `position`: Fractional index for ordering
- `task_count`: Cached task count

### Tasks Table
- `id`: Primary key
- `uuid`: Unique identifier
- `title`: Task title
- `description`: Task description
- `column_id`: Foreign key to Column
- `owner_id`: Foreign key to User (creator)
- `assignee_id`: Foreign key to User (assigned user)
- `position`: Fractional index for ordering
- `status`: Task status (backlog, in_progress, review, done)
- `metadata`: JSON additional data (tags, color, etc.)

## API Endpoints

### Tasks
- `POST /api/tasks` - Create a new task
- `GET /api/tasks/{id}` - Get task details
- `PATCH /api/tasks/{id}` - Update a task
- `DELETE /api/tasks/{id}` - Delete a task
- `POST /api/tasks/{id}/move` - Move task to different column/status
- `POST /api/tasks/reorder` - Reorder multiple tasks

### Boards
- `GET /api/boards` - List all boards
- `GET /api/boards/{id}` - Get board with columns and tasks
- `POST /api/boards` - Create a new board
- `PATCH /api/boards/{id}` - Update board
- `DELETE /api/boards/{id}` - Delete a board

## Frontend Integration

The Vue.js frontend components:
- `KanbanBoard.vue` - Main board component with columns
- `KanbanColumn.vue` - Individual column with task list
- `TaskCard.vue` - Task display card
- `QuickAddTask.vue` - Inline task creation form

## Real-time Updates

Mercure hub publishes events for:
- `task_created`: New task added
- `task_updated`: Task modified
- `task_moved`: Task moved between columns
- `task_deleted`: Task removed
- `column_created`: New column added
- `column_reordered`: Columns reordered
