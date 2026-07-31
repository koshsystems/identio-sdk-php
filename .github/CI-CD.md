# Identio PHP SDK CI/CD

The workflow runs on the `staging1-runner` labels at
`identio.staging1.hoshsystems.com` and keeps a runner-local bare mirror under
`/home/github-runner/git-mirrors/php`.

Pull requests only validate Composer and run PHPUnit. A qualifying push to
`main` runs the same checks, creates the next patch SemVer tag, and sends a
`repository_dispatch` event to PortalKit. A manual run may select a `minor` or
`major` increment; select `none` for a test-only run.

Create the `PORTALKIT_DISPATCH_TOKEN` Actions secret in this repository. It
must be a fine-grained token with **Contents: write** access only to
`PortalKit/portalkit-composer`; the permission is needed to create the dispatch
event. The repository settings must also permit the workflow `GITHUB_TOKEN` to
have `contents: write`, so the workflow can create its own release tag.
