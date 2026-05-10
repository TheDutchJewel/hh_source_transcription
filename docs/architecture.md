# Source Transcription Module – Architecture

## Overview

The *Source Transcription* module introduces a structured workflow for transcribing genealogical sources in webtrees.

It separates:
- **working state** (editable NOTE in webtrees)
- **historical state** (immutable revisions stored in module tables)

This enables versioning, collaboration, and integration with external transcription tools.

---

## Core Concept

### NOTE vs. Revision

| Concept      | Purpose              | Storage                  |
|--------------|----------------------|--------------------------|
| **NOTE**     | Current working copy | webtrees GEDCOM (`NOTE`) |
| **Revision** | Historical snapshot  | module database tables   |

Key principle:

> The NOTE is editable. The revision is immutable.

Workflow:

```text
NOTE (editable)
    ↓ Save as revision
Revision (immutable)
    ↓ Generate/update NOTE
NOTE (new working state)
```

## Data Model

An overview of the current database schema can be found in [docs/database/schema-3.sql.txt](database/schema-3.sql.txt).

### transcription_collaborators
id  
transcription_id  
user_id  
role  
invited_by_user_id  
invited_at  
accepted_at  
is_active  

This table stores the team for internal collaboration. It does not replace the transcription or revision tables.
The initial version directly assigns collaborators; `accepted_at` is reserved for a later explicit invitation/acceptance workflow.

### transcription_requests (planned)
id  
transcription_id  
provider_key  
interaction_model  
request_status  
external_reference  
external_url  
request_payload_json  
response_payload_json  
created_by_user_id  
created_at  
updated_at  
closed_at  

---

## Interaction Models

Manual (direct)  
interaction_model = manual_direct  

Internal collaboration  
interaction_model = internal_collaborative  

Automated (asynchronous)  
interaction_model = automated_async  

Crowd-based (asynchronous)  
interaction_model = crowd_async  

---

## Workflow

Manual:
1. Create transcription  
2. Create NOTE and link it to the selected media object  
3. Edit NOTE  
4. Save as revision  

Internal collaboration:
1. Open an existing transcription for collaboration
2. Optionally save the current working NOTE as a starting revision
3. Assign collaborators
4. Notify the team
5. Collaborators edit the working NOTE and save revisions
6. Any collaborator can set ready for review
7. The initiator can set final

Automated:
1. Submit request  
2. Import result  

Crowd:
1. Ask community  
2. Import answer  

---

## Services

- **CreateTranscriptionService**: Delegiert die Anlage einer neuen Transkription an den passenden Provider.
- **GetTranscriptionDetailService**: Lädt die vollständigen Daten einer Transkription inklusive Metadaten und Revisionshistorie.
- **SaveNoteAsRevisionService**: Erstellt einen Snapshot des aktuellen NOTE-Inhalts als unveränderliche Revision.
- **GenerateOrUpdateNoteService**: Synchronisiert den Inhalt einer webtrees NOTE mit den Daten aus dem Modul und verknüpft die NOTE primär mit dem ausgewählten Medienobjekt. Ohne Medienobjekt wird die Quelle als Fallback verwendet.
- **EnsureTagNoteService**: Stellt sicher, dass das entsprechende webtrees-Tag (NOTE) für die Verknüpfung existiert und demselben Ziel wie die Transkription zugeordnet ist.
- **OpenCollaborationService**: Öffnet eine bestehende Transkription für interne Zusammenarbeit, legt das Team fest und delegiert an den internen Provider.
- **CollaborationStatusService**: Setzt kollaborative Statusübergänge mit Rollenprüfung.
- **CollaborationNotificationService**: Informiert Teammitglieder über Kollaborationsereignisse über den webtrees-MessageService.

---

## Providers

- **TranscriptionProviderInterface**: Definiert den gemeinsamen Basiskontrakt für Transkriptions-Provider.
- **CreatesTranscriptionsInterface**: Markiert Provider, die neue Transkriptionen anlegen können.
- **OpensCollaborationInterface**: Markiert Provider, die eine bestehende Transkription für Zusammenarbeit öffnen können.
- **TranscriptionProviderFactory**: Erzeugt den passenden Provider anhand des Provider-Keys.
- **ManualTranscriptionProvider**: Kapselt den aktuellen manuellen Workflow inklusive Anlage der Transkription, erster Revision, Arbeits-NOTE und Tag-NOTE.
- **InternalCollaborationProvider**: Kapselt das Öffnen einer bestehenden Transkription für interne Zusammenarbeit. Quelle, Medienobjekt, Arbeits-NOTE und bisherige Revisionen bleiben erhalten.

---

## Gateways

- **SharedNoteGateway**: Abstraktionsschicht für den Zugriff auf webtrees NOTE-Datensätze (Shared Notes). NOTE-Datensätze werden über webtrees-Record-APIs erzeugt und aktualisiert, damit CHAN-Daten, Pending Changes und interne Links korrekt entstehen.
- **SourceGateway**: Ermöglicht den Zugriff auf webtrees Quellen (SOURce-Records), deren Struktur und den Fallback-Link einer NOTE zur Quelle.
- **MediaObjectGateway**: Dient zum Abrufen von Informationen über Medienobjekte (Sichtbarkeit) und deren Mediendateien (Metadaten) sowie zum primären Verknüpfen von NOTEs mit OBJE-Datensätzen.

---

## UI

### Pages
- **Dashboard**: Zentrale Übersicht über alle vorhandenen Transkriptionen.
- **Create**: Interface zur manuellen Erstellung einer Transkription (Auswahl von Quelle und Medium).
- **Detail**: Die Hauptarbeitsansicht. Enthält den Text-Editor für die aktuelle NOTE, zeigt das verknüpfte Medium an und listet die Revisionshistorie inklusive erzeugter NOTE und protokollierter NOTE-Änderung auf.

### Actions / API
- **UpdateCurrentNoteAction**: Speichert den aktuellen Text des Editors in der webtrees NOTE.
- **SaveNoteAsRevisionAction**: Archiviert den aktuellen Stand der NOTE als neue, unveränderliche Revision.
- **MediaForSourceAction**: Liefert via JSON die verfügbaren Medienobjekte für eine gewählte Quelle (wird im Create-Dialog genutzt).

---

## State machine


### States
```
enum TranscriptionStatus: string
{
case NEW = 'new';
case IN_PROGRESS = 'in_progress';
case READY_FOR_REVIEW = 'ready_for_review';
case FINAL = 'final';
case REOPENED = 'reopened';
case CANCELLED = 'cancelled';
}
```

### Transitions
```text
enum TranscriptionTransition: string
{
case START = 'start';
case SUBMIT_FOR_REVIEW = 'submit_for_review';
case APPROVE = 'approve';
case REOPEN = 'reopen';
case CANCEL = 'cancel';
}
```

---

## File Structure

```text
.
├── .gitignore
├── LICENSE
├── README.md
├── autoload.php
├── composer.json
├── latest-version.txt
├── module.php
├── docs/
│   ├── architecture.md
│   ├── database/
│   │   ├── schema-1.sql.txt
│   │   ├── schema-2.sql.txt
│   │   └── schema-3.sql.txt
│   ├── images/
│   │   └── ui/
│   │       ├── control_panel.png
│   │       ├── create_manual.png
│   │       ├── dashboard.png
│   │       └── details.png
│   └── provider/
│       ├── internal.md
│       ├── manual.md
│       └── transkribus.md
├── resources/
│   ├── lang/
│   │   ├── de.mo
│   │   ├── de.po
│   │   ├── nl.mo
│   │   └── nl.po
│   └── views/
│       ├── admin-settings.phtml
│       ├── create-manual.phtml
│       ├── dashboard.phtml
│       └── detail.phtml
└── src/
    ├── SourceTranscription.php
    ├── Application/
    │   ├── Dto/
    │   │   ├── CreateTranscriptionCommand.php
    │   │   └── OpenCollaborationCommand.php
    │   ├── Factory/
    │   │   └── NoteContentFactory.php
    │   ├── Provider/
    │   │   ├── CreatesTranscriptionsInterface.php
    │   │   ├── InternalCollaborationProvider.php
    │   │   ├── ManualTranscriptionProvider.php
    │   │   ├── OpensCollaborationInterface.php
    │   │   ├── TranscriptionProviderFactory.php
    │   │   └── TranscriptionProviderInterface.php
    │   └── Service/
    │       ├── CollaborationNotificationService.php
    │       ├── CollaborationStatusService.php
    │       ├── CreateTranscriptionService.php
    │       ├── EnsureTagNoteService.php
    │       ├── GenerateOrUpdateNoteService.php
    │       ├── GetTranscriptionDetailService.php
    │       ├── OpenCollaborationService.php
    │       └── SaveNoteAsRevisionService.php
    ├── Domain/
    │   ├── Entity/
    │   │   ├── NoteLink.php
    │   │   ├── Transcription.php
    │   │   └── TranscriptionRevision.php
    │   └── ValueObject/
    │       ├── NoteStrategy.php
    │       ├── ProviderKey.php
    │       ├── ProviderLabel.php
    │       └── ProviderPresentation.php
    ├── Enum/
    │   ├── InteractionModel.php
    │   ├── PrimaryForm.php
    │   ├── PrimaryLanguage.php
    │   ├── PrimaryScript.php
    │   ├── RevisionOriginType.php
    │   ├── TranscriptionStatus.php
    │   ├── TranscriptionTransition.php
    │   └── TranscriptionType.php
    ├── Http/
    │   └── RequestHandlers/
    │       ├── CollaborationStatusAction.php
    │       ├── CreateManualAction.php
    │       ├── DashboardAction.php
    │       ├── DetailAction.php
    │       ├── MediaForSourceAction.php
    │       ├── OpenCollaborationAction.php
    │       ├── SaveNoteAsRevisionAction.php
    │       ├── StoreManualAction.php
    │       └── UpdateCurrentNoteAction.php
    ├── Infrastructure/
    │   ├── Persistence/
    │   │   ├── Repository/
    │   │   │   ├── NoteLinkRepository.php
    │   │   │   ├── RevisionRepository.php
    │   │   │   ├── TranscriptionCollaboratorRepository.php
    │   │   │   ├── SettingsRepository.php
    │   │   │   └── TranscriptionRepository.php
    │   │   └── Schema/
│   │       ├── Migration0.php
│   │       ├── Migration1.php
│   │       ├── Migration2.php
│   │       └── SchemaManager.php
    │   ├── Webtrees/
    │   │   ├── MediaObjectGateway.php
    │   │   ├── SharedNoteGateway.php
    │   │   └── SourceGateway.php
    │   └── WhatsNew/
    │       ├── WhatsNew0.php
    │       └── WhatsNewInterface.php
    └── Support/
        ├── HashService.php
        └── TranscriptionSlug.php
```

---

## Known Limitations

- Permission checks are still implemented at the UI/action level and should be reviewed before production use.
- Existing data created by earlier development versions may still contain old NOTE links to sources until the affected transcription or tag NOTE is saved again.
