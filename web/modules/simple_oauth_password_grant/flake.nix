{
  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-24.05";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = {
    self,
    nixpkgs,
    flake-utils,
  }: flake-utils.lib.eachDefaultSystem (system:
    let
      overlays = [];
      pkgs = import nixpkgs {
        inherit system overlays;
      };
      # Embedded drupalspoons setup script.
      setupDrupal = pkgs.writeShellScriptBin "setup-drupal" ''
        composer init --no-interaction --quiet --name=drupalspoons/template --stability=dev

        composer config allow-plugins.composer/installers true
        composer config allow-plugins.cweagans/composer-patches true
        composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
        composer config allow-plugins.drupal/core-composer-scaffold true
        composer config allow-plugins.drupalspoons/composer-plugin true
        composer config allow-plugins.phpstan/extension-installer true

        # Accept a constraint for composer-plugin.
        echo -e "\n\n\nInstalling composer-plugin"
        composer require --dev --no-interaction drupalspoons/composer-plugin:$COMPOSER_PLUGIN_CONSTRAINT

        echo -e "\n\n\nPreparing $COMPOSER"
        composer drupalspoons:composer-json

        if [[ -z "$COMPOSER_PLUGIN_PREPARE" ]] || [[ "$COMPOSER_PLUGIN_PREPARE" != "true" ]] ; then
          echo -e "\nConfiguring project codebase for local tests"
          composer drupalspoons:configure
        fi

        echo -e "\nInstalling dependencies"
        composer update --prefer-stable --no-interaction --no-progress
        echo -e "\nConditionally installing Prophecy"
        composer drupalspoons:prophecy
      '';
      prepareEnv = { drupalVersion }: ''
        export DRUPAL_CORE_CONSTRAINT="^${drupalVersion}"
        export COMPOSER_PLUGIN_CONSTRAINT="^2"
        export COMPOSER="composer.spoons.json"
        export COMPOSER_CACHE_DIR="/tmp/composer-cache"
        export WEB_ROOT="web"
        export NONINTERACTIVE="1"
        export COMPOSER_NO_INTERACTION="1"
        export WEB_PORT="9000"
        export SIMPLETEST_BASE_URL="http://localhost:$WEB_PORT"
        export SIMPLETEST_DB="sqlite://localhost/sites/default/files/.sqlite"
        export PATH="$PATH:$(pwd)/vendor/bin"

        echo "DRUPAL_CORE_CONSTRAINT=^${drupalVersion}" > .composer-plugin.env
        echo "COMPOSER_PLUGIN_CONSTRAINT=^2" >> .composer-plugin.env

        echo ""
        echo "> Setup dependencies and drupal"
        echo "> \$ setup-drupal"
        echo ">"
        echo "> Run PHPCS"
        echo "> \$ composer phpcs"
        echo ">"
        echo "> Run PHPUnit"
        echo "> \$ composer unit"
        echo ">"
        echo "> Run Drupal-Check"
        echo "> \$ composer drupal-check"
      '';
    in {
      devShells = rec {
        # PHP 8.1 / Drupal 10
        php81_drupal10 = pkgs.mkShell {
          buildInputs = with pkgs; [
            php81
            php81Packages.composer
            setupDrupal
          ];

          shellHook = prepareEnv { drupalVersion = "10"; };
        };

        # PHP 8.2 / Drupal 10
        php82_drupal10 = pkgs.mkShell {
          buildInputs = with pkgs; [
            php82
            php82Packages.composer
            setupDrupal
          ];

          shellHook = prepareEnv { drupalVersion = "10"; };
        };

        # PHP 8.3 / Drupal 10
        php83_drupal10 = pkgs.mkShell {
          buildInputs = with pkgs; [
            php83
            php83Packages.composer
            setupDrupal
          ];

          shellHook = prepareEnv { drupalVersion = "10"; };
        };


        default = php82_drupal10;
      };

      formatter = pkgs.alejandra;
    }
  );
}


