# Release Process

The following steps should be followed to publish a release of the WP Irving plugin.

1. If this is a major (X.0.0) or minor release (X.Y.0), Create release branch off of the `main` branch: `git checkout -b release/{X.Y}`. If this is a point release (X.Y.Z), check out the relevant release branch `git checkout release/{X.Y}` that already exists.
2. Update version number in the plugin header
3. Update changelog in readme.txt and run `npm run readme` to convert the txt file to a markdown file.
4. Open a PR from the release branch back to `main`.
5. Create a release candidate:
    * Draft a release from [the GitHub new release page](https://github.com/alleyinteractive/wp-irving/releases/new).
    * Tag the version (e.g., 1.1.0-RC1) and set the target to the release branch.
    * Name the release title using the release version, e.g. 1.1.0 RC1
    * Copy the changelog and add any other relevant info you want into the release notes
    * **Mark the release as a pre-release**
    * Publish the release
6. Once tests pass and someone has approved the PR, and after confirming the RC is good, create a release:
    * Draft a release from [the GitHub new release page](https://github.com/alleyinteractive/wp-irving/releases/new).
    * Tag the version (e.g., 1.1.0) and set the target to the release branch.
    * Name the release title using the release version, e.g. 1.1.0
    * Copy the changelog from the RC version and add any other relevant info you want into the release notes
    * Publish the release
7. Merge the release branch back to `main`
8. Publish a release announcement on the Irving Basecamp

