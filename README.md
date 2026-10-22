# TeamFlow — Team Collaboration & Task Management

TeamFlow is a full-stack team collaboration and task management application built with a **Laravel REST API** and a **React + TypeScript** frontend.

It helps teams organize workspaces, manage team members, assign tasks, collaborate through comments, and receive notifications when tasks are assigned.

## Features

### Authentication
- User registration and login
- Secure session-based authentication with Laravel Sanctum
- Logout and session restoration
- Protected API endpoints

### Workspace Management
- Create and view workspaces
- Select a workspace
- Delete workspaces with owner-only authorization
- Automatically delete related tasks, comments, and memberships when a workspace is deleted

### Team Member Management
- View workspace members
- Add and remove team members
- Support for Owner and Member roles
- Role-based access control
- Prevent duplicate memberships
- Restrict member management to workspace owners

### Task Management
- Create, view, update, and delete tasks
- Set task status and priority
- Assign tasks to workspace members
- Set task due dates
- Display task creators and assignees
- Validate task assignments against workspace membership
- Prevent unauthorized access across workspaces

### Task Comments
- View task comments
- Add and delete comments
- Restrict comment access to workspace members

### Notifications
- Notify users when a task is assigned to them during task creation
- View notifications
- Mark individual notifications as read
- Mark all notifications as read
- Track unread notifications

### Security and Testing
- Authentication and authorization checks
- Workspace-level access control
- Protection against unauthorized resource access
- Automated backend tests using PHPUnit

## Tech Stack

| Technology | Purpose |
|---|---|
| PHP 8.3 | Backend programming language |
| Laravel 12 | REST API development |
| Laravel Sanctum | SPA authentication |
| React | Frontend user interface |
| TypeScript | Type-safe frontend development |
| Vite | Frontend development and production builds |
| MySQL / compatible relational database | Data persistence |
| PHPUnit | Automated backend testing |

## Project Structure

The project is organized into two separate applications:

```text
TeamFlow/
├── teamflow-api/    # Laravel REST API
└── teamflow-web/    # React + TypeScript frontend
```

## Getting Started

### Prerequisites

Make sure you have the following installed:

- PHP 8.3 or a compatible version
- Composer
- Node.js and npm
- MySQL or another database supported by your Laravel configuration
- Git

### 1. Clone the Repositories

Clone the backend and frontend repositories separately:

```bash
git clone https://github.com/Cooooooooooder/teamflow-api
git clone https://github.com/Cooooooooooder/teamflow-web
```


### 2. Set Up the Backend

Navigate to the Laravel project directory:

```bash
cd teamflow-api
```

Install PHP dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

On Windows Command Prompt, you can use:

```cmd
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teamflow
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Update the database name and credentials to match your local environment.

Run the database migrations:

```bash
php artisan migrate
```

Start the Laravel development server:

```bash
php artisan serve
```

The backend will normally be available at:

```text
http://127.0.0.1:8000
```

### 3. Configure SPA Authentication

TeamFlow uses Laravel Sanctum for session-based authentication between the React frontend and Laravel backend.

Configure the relevant environment variables in the backend `.env` file. For local development, the configuration should match your frontend origin and backend host.

Example:

```env
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost:8000,127.0.0.1:8000
```

Ensure that the Laravel Sanctum stateful middleware and CORS configuration are correctly configured for your frontend origin, including credentials support.

Refer to the [Laravel Sanctum documentation](https://laravel.com/docs/sanctum) for additional configuration details.

### 4. Set Up the Frontend

Open another terminal and navigate to the frontend directory:

```bash
cd teamflow-web
```

Install dependencies:

```bash
npm install
```

Configure the frontend API base URL if your existing API client requires an environment variable. Use the backend URL:

```text
http://localhost:8000
```

Start the Vite development server:

```bash
npm run dev
```

The frontend will normally be available at:

```text
http://localhost:5173
```

### 5. Run Backend Tests

From the backend directory, run:

```bash
php artisan test
```

The current verified test suite contains **52 passing tests and 147 assertions**. Results may change as the project evolves.

### 6. Build the Frontend

From the frontend directory, run:

```bash
npm run build
```

This command runs the TypeScript build checks and creates an optimized production build using Vite.

## Authorization Rules

TeamFlow enforces workspace-level access restrictions.

- Guests cannot access protected resources.
- Workspace members can access permitted workspace resources.
- Only workspace owners can manage members and delete their workspace.
- Tasks cannot be assigned to users who are not members of the relevant workspace.
- Users cannot access another user's private notifications.

## Future Improvements

Potential future enhancements include:

- Real-time notifications
- Email invitations
- Task attachments
- Task filtering and search
- Kanban board view
- Activity history
- Deployment with a production database

These are possible future improvements, not features currently claimed as implemented.

## Project Goals

This project demonstrates practical experience with:

- RESTful API design
- Laravel controllers, models, migrations, and relationships
- Authentication and authorization
- React and TypeScript integration
- Frontend state management and API communication
- Relational database design
- Automated feature testing
- Responsive user interface development

## License

This project is available for educational and portfolio purposes. Add a license file if you intend to distribute it under a specific open-source license.
