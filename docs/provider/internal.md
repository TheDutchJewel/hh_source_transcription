# Internal collaboration provider

The internal collaboration provider opens an existing transcription for collaboration by several webtrees users.

It does not create a new transcription. Source, media object, current working NOTE, tag NOTE, and previous revisions remain unchanged.

## Roles

- Initiator: starts collaboration, selects collaborators, can finalize or reopen the collaboration.
- Collaborator: can edit the working NOTE, save revisions, and submit the transcription for review.

## Workflow

1. Open an existing transcription.
2. Start internal collaboration.
3. Optionally save the current working NOTE as a starting revision.
4. Select collaborators from eligible webtrees users.
5. Notify collaborators using webtrees messaging/contact preferences.
6. Collaborators edit the current working NOTE and save revisions.
7. All active collaborators are notified about new revisions.
8. Any active collaborator can set the status to ready for review.
9. The initiator can set the status to final.
10. The initiator can reopen the transcription if more work is needed.

## Data

Collaborators are stored in `transcription_collaborators`.
The table stores the team relation only. It does not duplicate revision content.

`accepted_at` is reserved for a later explicit invitation/acceptance workflow.
