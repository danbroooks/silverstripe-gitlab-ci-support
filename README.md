
# Gitlab CI Integration for SilverStripe Modules

Testing SilverStripe modules in CI environments requires some setting up.
For modules to be under independent version control in git,
a SilverStripe test enviroment needs to be installed underneath when they are pulled into GitLab CI for testing.
This module automates that build step.

## Setup

Add the following to the start of your build steps:

  ```bash
  git clean -ffdx
  rm ./gitlab-ci-support -fr
  git clone https://github.com/dangerdan/silverstripe-gitlab-ci-support.git ./gitlab-ci-support
  php ./gitlab-ci-support/gitlab-ci-support.php
  ```

This script will copy a `composer.json` file from the root of your module repository into the test environment,
so be sure to add one to ensure any dependencies your module may have so they can be installed.
The additional `git clean` command is required to clean out any submodules that might have been installed as dependencies
in previous tests. The two f's in `-ffdx` is not a typo, it's required to make sure `clean` removes submodules.

This module doesn't actually run `composer install` so be sure to add that to your build steps.
A typical build might look like this:

  ```bash
  git clean -ffdx
  rm ./gitlab-ci-support -fr
  git clone https://github.com/dangerdan/silverstripe-gitlab-ci-support.git ./gitlab-ci-support
  php ./gitlab-ci-support/gitlab-ci-support.php

  composer install
  cp ~/_ss_environment.php ./_ss_environment.php
  sake dev/build "flush=all"
  phpunit
  ```
