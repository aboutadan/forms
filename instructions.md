A dynamic form builder built with PHP 8+ and MySQL 8. Users create forms, publish them, and collect submissions.

CREATING A FORM

1. Go to the Create Form page
2. Enter a form title
3. Add questions one at a time using the form builder
4. Drag and drop questions to reorder them
5. Click Save Draft to save your progress

Each question requires a label and a type. You can mark any question as required, which displays a red asterisk on the published form.

FIELD TYPES

- text — Single-line text input
- textarea — Multi-line text area
- email — Validates email format on submission
- number — Accepts numeric values only
- date — Date picker, expects YYYY-MM-DD format
- select — Dropdown menu, single choice
- radio — Radio buttons, single choice
- checkbox — Checkboxes, multiple choice

Select, radio, and checkbox types require a list of options. Enter one option per line in the options box.

CONDITIONAL LOGIC

Questions can be shown or hidden based on a previous question's answer. Click "Add condition" on any question to set this up.

A condition has three parts:

1. When question — pick a previous question
2. Operator — how to compare the answer
3. Value — the value to compare against

Available operators depend on the parent question's type:

- Is answered — any non-empty answer (all types)
- Equals — exact match (all types)
- Does not equal — inverse of equals (all types)
- Contains — substring match (text, textarea, email only)
- Greater than — numeric or date comparison (number, date only)
- Less than — numeric or date comparison (number, date only)

When a condition is not met, the question is hidden, its inputs are cleared, and it is excluded from both validation and the saved response.

PUBLISHING A FORM

1. From the form list or the edit page, click Publish
2. Confirm on the publish page
3. The form is now live and can accept submissions

Publishing freezes a copy of your form structure. You can continue editing the draft without affecting the published version. Publishing again overwrites the previous published version.

Only published forms can be filled out by respondents.

SHARING A FORM

Send respondents the view link: `/view.php?form_id=<id>`

No login is required to fill out a published form. The form accepts unlimited submissions.

VIEWING RESPONSES

Go to the Responses page for your form. You will see:

- Total submission count
- A search box to filter across all submission data
- Each submission displayed as a card with the submission ID, timestamp, and a table of questions and answers
- An expandable "View JSON" section on each submission for the raw response data

Multiple-choice answers (checkboxes) are displayed as a comma-separated list.

VALIDATION

Required fields must be filled before submission. Additional validation runs based on field type:

- email — must be a valid email address
- number — must be a numeric value
- date — must be a valid date in YYYY-MM-DD format
- select / radio — answer must match one of the defined options
- checkbox — all selected values must be in the defined options

Hidden questions (condition not met) are skipped during validation.
