# Forms

A dynamic form builder and submission system (Google Forms style) built with PHP 8 and MySQL 8. Forms are defined as JSON schemas, built through a drag-and-drop UI, and rendered as public pages for respondents.

## Requirements

- **Docker** and **Docker Compose**

That's it. The Docker setup includes:

| Service    | Image                   | Port |
| ---------- | ----------------------- | ---- |
| PHP/Apache | `php:8.2-apache`        | 80   |
| MySQL      | `mysql:8.0`             | 3306 |
| phpMyAdmin | `phpmyadmin/phpmyadmin` | 8081 |

## Getting Started

```bash
# 1. Start the containers
docker compose up -d

# 2. Run the database migration (creates the tables)
docker exec formatos-app php migrate.php

# 3. Open in your browser
http://localhost
```

## How It Works

### Workflow

1. **Create** a form at `create.php` using the visual builder
2. **Publish** the draft at `publish.php` (copies `draft_schema` to `published_schema`)
3. **Share** the public form link: `view.php?form_id=<id>`
4. **View responses** at `responses.php?form_id=<id>`

### Question Types

| Type       | HTML Control    |
| ---------- | --------------- |
| `text`     | Text input      |
| `textarea` | Multi-line text |
| `email`    | Email input     |
| `number`   | Number input    |
| `date`     | Date picker     |
| `select`   | Dropdown        |
| `radio`    | Single choice   |
| `checkbox` | Multiple choice |

### Conditional Logic

Questions can be shown or hidden based on a previous question's answer. Each condition has three parts:

- **When question** — a previous question in the form
- **Operator** — the comparison to perform
- **Value** — the value to compare against

Available operators depend on the parent question type:

| Operator       | Applies to             | Description                   |
| -------------- | ---------------------- | ----------------------------- |
| Is answered    | All types              | Any non-empty answer          |
| Equals         | All types              | Exact match                   |
| Does not equal | All types              | Not an exact match            |
| Contains       | Text, Long text, Email | Answer includes the substring |
| Greater than   | Number                 | Numeric comparison            |
| Less than      | Number                 | Numeric comparison            |
| Is after       | Date                   | Date is after the value       |
| Is before      | Date                   | Date is before the value      |

When a condition is **not met**, the question is hidden from the respondent, its inputs are cleared, and it is excluded from both validation and stored responses.

## Project Structure

```
formatos/
  index.php          Welcome page with navigation
  create.php         Form builder (drag-and-drop UI)
  publish.php        Publish a draft to make it live
  view.php           Public form for respondents
  list.php           List all forms with actions
  responses.php      View submissions for a form
  forms.php          Shared helpers (validation, condition evaluation)
  db.php             Database connection (.env loader)
  migrate.php        CLI script to create tables
  schema.sql         Table definitions
  docker-compose.yml Docker services
  Dockerfile         PHP 8.2 + Apache + pdo_mysql
  .env               Database credentials
```

## Database Schema

Two tables in the `forms` database:

**`forms`** — stores form definitions

| Column             | Type         | Description                  |
| ------------------ | ------------ | ---------------------------- |
| `id`               | BIGINT PK    | Auto-increment               |
| `owner_id`         | BIGINT       | Form owner                   |
| `title`            | VARCHAR(255) | Form title                   |
| `draft_schema`     | JSON         | Editable schema              |
| `published_schema` | JSON         | Live schema (set on publish) |
| `is_published`     | TINYINT      | 0 = draft, 1 = published     |

**`form_submissions`** — stores respondent answers

| Column         | Type      | Description              |
| -------------- | --------- | ------------------------ |
| `id`           | BIGINT PK | Auto-increment           |
| `form_id`      | BIGINT FK | References `forms.id`    |
| `responses`    | JSON      | Key-value map of answers |
| `submitted_at` | TIMESTAMP | Submission time          |

## Environment Variables

Configured in `.env` (loaded automatically):

```
DB_HOST=db
DB_PORT=3306
DB_NAME=forms
DB_USER=root
DB_PASSWORD=root
```

## Example Schema

```json
{
  "questions": [
    {
      "id": "department",
      "type": "select",
      "label": "Department",
      "required": true,
      "options": ["HR", "IT", "Finance"]
    },
    {
      "id": "employee_id",
      "type": "text",
      "label": "Employee ID",
      "required": true,
      "condition": {
        "question_id": "department",
        "operator": "equals",
        "value": "IT"
      }
    }
  ]
}
```

In this example, "Employee ID" only appears when the respondent selects "IT" as their department.
