# Transkribus Integration – Concept and Implementation Sketch

## Overview

This document describes the planned integration of Transkribus as a transcription provider within the webtrees module:

[hh_source_transcription](https://github.com/hartenthaler/hh_source_transcription)

The goal is to integrate Transkribus in a modular, extensible way that aligns with the existing provider abstraction and supports asynchronous workflows.

---

## Architectural Principles

- Transkribus is implemented as a **provider module**
- Providers are instantiated via a **factory**
- The core module remains provider-agnostic
- Transkribus-specific logic is fully encapsulated

---

## Components

### 1. Provider Module

A Transkribus provider will be implemented with:

- Provider key: `transkribus`
- Interaction model: `automated_async`
- Responsibilities:
  - Upload image
  - Create job
  - Poll job status
  - Download transcription result

---

### 2. User Management

Each webtrees user can store Transkribus credentials.

Scope:
- API key (or token)
- Optional username

Requirements:
- Stored securely (encrypted if possible)
- Editable via control panel page

---

### 3. Job Management

A new database table:

```
transcription_jobs
```

Fields:

```
id
transcription_id
provider_key
status
external_job_id
media_file_reference
created_by_user_id
created_at
updated_at
finished_at
error_message
```

Status values:

```
created
submitted
processing
completed
failed
cancelled
```
The internal job status is connected to the general transcription status machine.

---

### 4. Job Monitoring UI

A webtrees admin can manage all jobs. A webtrees editor can manage only his own jobs.

A simple admin/user page:

**Transcription Jobs Dashboard**

Features:

- List all jobs
- Filter by status
- Show:
  - transcription
  - provider
  - status
  - timestamps
- Actions:
  - Cancel job

---

### 5. Workflow

#### Step 1: Trigger

User (webtrees editor or higher) selects:

```
Transcribe with Transkribus
```

Availability rules:

- Only for supported media types (image)
- Only within size limits

If not available:

- Disabled button
- Tooltip explaining why

---

#### Step 2: Input Selection

For V1:

- Only first image file in selected media object
- Only first page in that media file

---

#### Step 3: Job Creation

- Upload image to Transkribus
- Create transcription job
- Store job in `transcription_jobs`

---

#### Step 4: Asynchronous Processing

- Job status is polled periodically
- Status updated in database
- message is sent to user (Flash-message if logged in or internal message/eMail)

---

#### Step 5: Completion

When job is finished:

- Download TXT transcription
- Store as new revision
- Update NOTE via existing logic

---

#### Step 6: Error Handling

If job fails:

- Store error message
- Mark job as `failed`

---

#### Step 7: Cancelation

User can:

- Cancel job via UI
- Update status to `canceled`

---

## Output Handling

Initial:

- Only TXT format

---

## Integration Points

- Provider factory

---

## Future Extensions

- Multi-page support
- Media file conversion (resize, format)
- Output handling (PAGE XML, TEI, structured extraction)
- Multiple Transkribus collections
- Model selection based on script, language, date of source
- Integration with translation workflows

---

## Questions

- Should the user be able to use / linked to the interactive processing at Transkribus web page?
- Should we wait (some weeks?) for the new Transkribus API or implement the existing API?

---

## Summary

This design introduces Transkribus as a asynchronous provider with:

- minimal viable integration
- clean architecture

The implementation focuses on:

- correctness
- robustness
- gradual feature expansion
