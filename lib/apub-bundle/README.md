# survos/activity-pub-bundle

ActivityPub federation (actor documents, WebFinger, inbox/outbox, RSA keypairs, draft-cavage
HTTP Signatures, async delivery) with **no knowledge of what's being federated**.

Ported from `survos-sites/scanseum` (see [issue #16](https://github.com/survos-sites/scanseum/issues/16)),
where the same code was tightly coupled to `App\Entity\User` and `App\Entity\Bookmark`. Two
decoupling rules drive every design decision here:

- **No FK to the host app's user entity.** An actor is identified by `(subjectType, subjectId)` —
  plain strings the app supplies, the same pattern `survos/claims-bundle` uses for `Claim::subjectType`
  / `subjectId`. The bundle never references a concrete `User` class.
- **No knowledge of what's being federated.** `publish()` takes plain scalars (subject identity,
  object IRI/name, optional target IRI/name, published timestamp) — never an app entity like
  `Bookmark`. The app's own event listener is responsible for turning a domain event into that
  call.

## Status

Scaffolding only — see `survos-sites/scanseum#16` for the extraction plan. Being built out here,
in `apub-demo`, before moving to `survos/mono/bu/activity-pub-bundle`.
