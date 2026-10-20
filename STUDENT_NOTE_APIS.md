# Student & Public Note APIs Documentation

**Base URL:** `https://apiv2.asteghi.com/api`  
**Authentication:** Required for user endpoints via Bearer Token (`auth:sanctum`).  
**Headers:**
```http
Authorization: Bearer <STUDENT_SANCTUM_TOKEN>
Accept: application/json
```

---

## 1. Get Chapter Notes (Two-Tab Student UI)

Retrieves all active notes for a specific chapter, cleanly separated into `teacher_note` and `ai_note` to power a two-tab mobile or web interface.

- **Method:** `GET`
- **Route:** `/api/chapters/{chapterId}/notes`
- **Controller:** `App\Http\Controllers\Api\NoteController@getByChapter`
- **Request Body:** None (GET request)

### URL Parameters
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `chapterId` | `integer` | Yes | The ID of the chapter (e.g., `10`) |

---

### Response Body: Scenario A — Both Teacher Note & AI Note Exist (200 OK)

```json
{
  "status": "success",
  "data": {
    "chapter": {
      "id": 10,
      "title": "Vector Quantities",
      "chapter_number": 1
    },
    "teacher_note": {
      "id": 15,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Mr. Abebe's Physics Chapter 1 Notes",
      "content": "# Vector Quantities\n\nVectors possess both magnitude and direction.\n\n## Core Formulas\n- $\\vec{R} = \\vec{A} + \\vec{B}$\n- $|\\vec{A}| = \\sqrt{A_x^2 + A_y^2}$",
      "is_ai_generated": false,
      "is_active": true,
      "created_at": "2026-09-18T10:00:00.000000Z",
      "updated_at": "2026-09-19T06:00:00.000000Z",
      "grade": {
        "id": 1,
        "name": "Grade 10"
      },
      "subject": {
        "id": 3,
        "title": "Physics"
      },
      "chapter": {
        "id": 10,
        "title": "Vector Quantities",
        "chapter_number": 1
      }
    },
    "has_teacher_note": true,
    "ai_note": {
      "id": 22,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Vector Quantities Notes",
      "content": "# Vector Quantities Notes\n\n**Subject:** Physics • **Grade:** Grade 10\n\n## 1. Overview & Learning Objectives\n- Scalar vs Vector quantities.\n- Resolution of vectors into orthogonal components.",
      "is_ai_generated": true,
      "is_active": true,
      "created_at": "2026-09-19T05:00:00.000000Z",
      "updated_at": "2026-09-19T07:15:00.000000Z",
      "grade": {
        "id": 1,
        "name": "Grade 10"
      },
      "subject": {
        "id": 3,
        "title": "Physics"
      },
      "chapter": {
        "id": 10,
        "title": "Vector Quantities",
        "chapter_number": 1
      }
    },
    "has_ai_note": true
  },
  "chapter": {
    "id": 10,
    "title": "Vector Quantities",
    "chapter_number": 1
  },
  "teacher_note": { ... },
  "has_teacher_note": true,
  "ai_note": { ... },
  "has_ai_note": true
}
```

---

### Response Body: Scenario B — Teacher Note Not Uploaded Yet (`teacher_note: null`) (200 OK)

When no teacher note has been published yet, `teacher_note` is `null` and `has_teacher_note` is `false`. Frontend can immediately display an empty state under the "Teacher Note" tab.

```json
{
  "status": "success",
  "data": {
    "chapter": {
      "id": 10,
      "title": "Vector Quantities",
      "chapter_number": 1
    },
    "teacher_note": null,
    "has_teacher_note": false,
    "ai_note": {
      "id": 22,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Vector Quantities Notes",
      "content": "# Vector Quantities Notes\n\nComprehensive AI synthesized study guide...",
      "is_ai_generated": true,
      "is_active": true,
      "created_at": "2026-09-19T05:00:00.000000Z",
      "updated_at": "2026-09-19T07:15:00.000000Z"
    },
    "has_ai_note": true
  },
  "chapter": {
    "id": 10,
    "title": "Vector Quantities",
    "chapter_number": 1
  },
  "teacher_note": null,
  "has_teacher_note": false,
  "ai_note": { ... },
  "has_ai_note": true
}
```

---

### Response Body: Scenario C — Chapter Not Found (404 Not Found)

```json
{
  "status": "error",
  "message": "Chapter not found"
}
```

---

## 2. List Notes (Filterable by Grade, Subject, Chapter)

Fetch active notes with optional query parameter filtering. Returns notes ordered with Teacher notes first (`is_ai_generated: false`), followed by AI notes (`is_ai_generated: true`).

- **Method:** `GET`
- **Route:** `/api/notes`
- **Controller:** `App\Http\Controllers\Api\NoteController@index`
- **Request Body:** None (GET request)

### Query Parameters (All Optional)
| Query Param | Type | Description | Example |
| :--- | :--- | :--- | :--- |
| `grade_id` | `integer` | Filter notes by grade | `/api/notes?grade_id=1` |
| `subject_id` | `integer` | Filter notes by subject | `/api/notes?subject_id=3` |
| `chapter_id` | `integer` | Filter notes by chapter | `/api/notes?chapter_id=10` |
| `is_ai_generated`| `boolean` | `true`/`1` for AI notes only, `false`/`0` for Teacher notes only | `/api/notes?is_ai_generated=false` |

### Response Body (200 OK)

```json
{
  "status": "success",
  "message": "Notes retrieved successfully",
  "data": [
    {
      "id": 15,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Mr. Abebe's Physics Chapter 1 Notes",
      "content": "Vectors possess both magnitude and direction...",
      "is_ai_generated": false,
      "is_active": true,
      "created_at": "2026-09-18T10:00:00.000000Z",
      "updated_at": "2026-09-19T06:00:00.000000Z",
      "grade": {
        "id": 1,
        "name": "Grade 10"
      },
      "subject": {
        "id": 3,
        "title": "Physics"
      },
      "chapter": {
        "id": 10,
        "title": "Vector Quantities",
        "chapter_number": 1
      }
    },
    {
      "id": 22,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Vector Quantities Notes",
      "content": "Comprehensive syllabus overview...",
      "is_ai_generated": true,
      "is_active": true,
      "created_at": "2026-09-19T05:00:00.000000Z",
      "updated_at": "2026-09-19T07:15:00.000000Z",
      "grade": {
        "id": 1,
        "name": "Grade 10"
      },
      "subject": {
        "id": 3,
        "title": "Physics"
      },
      "chapter": {
        "id": 10,
        "title": "Vector Quantities",
        "chapter_number": 1
      }
    }
  ]
}
```

---

## 3. Get Single Note by ID

Retrieve a specific note by its unique primary key ID.

- **Method:** `GET`
- **Route:** `/api/notes/{id}`
- **Controller:** `App\Http\Controllers\Api\NoteController@show`
- **Request Body:** None (GET request)

### URL Parameters
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `id` | `integer` | Yes | The ID of the note (e.g., `15`) |

### Response Body: Success (200 OK)

```json
{
  "status": "success",
  "message": "Note retrieved successfully",
  "data": {
    "id": 15,
    "chapter_id": 10,
    "grade_id": 1,
    "subject_id": 3,
    "title": "Mr. Abebe's Physics Chapter 1 Notes",
    "content": "# Vector Quantities\n\nVectors possess both magnitude and direction...",
    "is_ai_generated": false,
    "is_active": true,
    "created_at": "2026-09-18T10:00:00.000000Z",
    "updated_at": "2026-09-19T06:00:00.000000Z",
    "grade": {
      "id": 1,
      "name": "Grade 10"
    },
    "subject": {
      "id": 3,
      "title": "Physics"
    },
    "chapter": {
      "id": 10,
      "title": "Vector Quantities",
      "chapter_number": 1
    }
  }
}
```

### Response Body: Note Not Found (404 Not Found)

```json
{
  "status": "error",
  "message": "Note not found"
}
```

---

## 4. Exam Short Notes Listing

Retrieve revision and summary short notes prepared for exam preparation.

- **Method:** `GET`
- **Route:** `/api/exam-notes`
- **Controller:** `App\Http\Controllers\Api\ExamNoteController@index`
- **Request Body:** None (GET request)

### Query Parameters (All Optional)
| Query Param | Type | Description | Example |
| :--- | :--- | :--- | :--- |
| `grade_id` | `integer` | Filter by grade ID | `/api/exam-notes?grade_id=1` |
| `subject_id` | `integer` | Filter by subject ID | `/api/exam-notes?subject_id=3` |
| `chapter_id` | `integer` | Filter by chapter ID | `/api/exam-notes?chapter_id=10` |
| `q` or `search` | `string` | Search keyword in title or content | `/api/exam-notes?q=kinematics` |

### Response Body (200 OK)

```json
{
  "status": "success",
  "message": "Exam notes retrieved successfully",
  "data": [
    {
      "id": 15,
      "chapter_id": 10,
      "grade_id": 1,
      "subject_id": 3,
      "title": "Vectors Quick Revision",
      "content": "Key takeaways for national exam...",
      "is_ai_generated": false,
      "is_active": true,
      "created_at": "2026-09-18T10:00:00.000000Z",
      "updated_at": "2026-09-19T06:00:00.000000Z",
      "grade": {
        "id": 1,
        "name": "Grade 10"
      },
      "subject": {
        "id": 3,
        "title": "Physics"
      },
      "chapter": {
        "id": 10,
        "title": "Vector Quantities",
        "chapter_number": 1
      }
    }
  ]
}
```

---

## 5. Single Exam Short Note

- **Method:** `GET`
- **Route:** `/api/exam-notes/{id}`
- **Controller:** `App\Http\Controllers\Api\ExamNoteController@show`
- **Request Body:** None (GET request)

### Response Body (200 OK)

```json
{
  "status": "success",
  "message": "Exam note retrieved successfully",
  "data": {
    "id": 15,
    "chapter_id": 10,
    "grade_id": 1,
    "subject_id": 3,
    "title": "Vectors Quick Revision",
    "content": "Key takeaways for national exam...",
    "is_ai_generated": false,
    "is_active": true,
    "created_at": "2026-09-18T10:00:00.000000Z",
    "updated_at": "2026-09-19T06:00:00.000000Z"
  }
}
```

---

## Frontend Integration Guide (Student Two-Tab UI)

### Flutter / React / React Native Implementation Logic:

```typescript
// Example TypeScript interface
interface ChapterNotesResponse {
  status: "success" | "error";
  data: {
    chapter: {
      id: number;
      title: string;
      chapter_number: number;
    };
    teacher_note: NoteItem | null;
    has_teacher_note: boolean;
    ai_note: NoteItem | null;
    has_ai_note: boolean;
  };
}

// UI Tab Rendering logic:
// Tab 1: [ 👤 Teacher Note ]
if (!response.data.has_teacher_note || !response.data.teacher_note) {
  renderEmptyState("No teacher note available for this chapter yet.");
} else {
  renderMarkdown(response.data.teacher_note.content);
}

// Tab 2: [ ⚡ AI Study Guide ]
if (!response.data.has_ai_note || !response.data.ai_note) {
  renderEmptyState("AI study guide is generating...");
} else {
  renderMarkdown(response.data.ai_note.content);
}
```
