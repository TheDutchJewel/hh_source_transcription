# Manual Transcription – Concept and Implementation Sketch

## Overview

This document describes the manual transcription provider within the webtrees module:

[hh_source_transcription](https://github.com/hartenthaler/hh_source_transcription)

The goal is to allow users to create and edit transcriptions directly within webtrees.

---

## Architectural Principles

- Manual transcription is implemented as a **provider module**
- Providers are instantiated via a **factory**
- The core module remains provider-agnostic
- Manual-specific logic (direct editing) is fully encapsulated

---

## Components

### 1. Provider Module

The Manual provider is implemented with:

- Provider key: `manual`
- Interaction model: `manual_direct`
- Responsibilities:
  - Provide a UI for direct text entry
  - Handle direct storage of transcription text as revisions

---

### 2. User Management

No specific external credentials are required for manual transcription. Access is governed by standard webtrees permissions (Editor or higher).

---

### 3. Workflow

#### Step 1: Trigger

User (webtrees editor or higher) selects:

```
Create manual transcription
```

---

#### Step 2: Input Selection

The user selects:
- Target Source record
- Optional Media object from the source

---

#### Step 3: Transcription Entry

The user provides metadata and the transcription text:
- Title
- Primary Language, Script, and Form
- Initial transcription text

---

#### Step 4: Storage

- A new transcription record is created in the database
- The initial text is stored as the first revision
- Revision origin is marked as `manual_entry`
- A shared NOTE is generated through the webtrees record API
- If a media object is selected, the NOTE is linked to the `SOUR:OBJE` record; otherwise it is linked to the `SOUR` record

---

#### Step 5: Completion

The transcription is immediately available for viewing, and the user is redirected to the transcription detail page.

---

## Output Handling

- Plain text
- Stored as module revisions and as a standard webtrees shared NOTE working copy
- optional integration of the Rich Text Editor TinyMDE (via custom module linkenhancer)
- Side-by-side view of media image and editor

---

## Integration Points

- Provider factory
- Action handlers: `CreateManualAction`, `StoreManualAction`
- Service: `CreateTranscriptionService::createManual()`

---

## Future Extensions

- Support for TEI/XML or other structured formats via manual entry

---

## Summary

This design implements the "Manual" provider as a core feature for:

- direct manual transcription within the webtrees interface
- seamless integration with the revision and storage system

The implementation focuses on:

- simplicity
- immediate availability of results
