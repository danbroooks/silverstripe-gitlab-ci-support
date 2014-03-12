
# Gitlab CI Integration for SilverStripe Modules

Testing SilverStripe modules in CI environments requires some setting up.
For modules to be under independent version control in git,
a SilverStripe test enviroment needs to be installed underneath when they are pulled into GitLab CI for testing.
This module automates that build step.

## Setup

Add the following to the start of your build steps:

  ```bash
  rm ./gitlab-ci-support -fr
  git clone https://github.com/dangerdan/silverstripe-gitlab-ci-support.git ./gitlab-ci-support
  php ./gitlab-ci-support/gitlab-ci-support.php
  ```

This script will copy a `composer.json` file from the root of your module repository into the test environment,
so be sure to add one to ensure any dependencies are installed.

This module doesn't actually run `composer install` so be sure to add that to your build steps.
A typical build may carry on like this:

  ```bash
  composer install
  cp ~/_ss_environment.php ./_ss_environment.php
  sake dev/build "flush=all"
  phpunit
  ```
