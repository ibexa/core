Feature: app/console
    # IBX-12204: ConsoleCommandListener no longer mutates the shared SiteAccess singleton in place
    # (it only calls SiteAccessService::changeSiteAccess() now), so any command that reads the
    # CLI-resolved siteaccess by type-hinting that shared service directly no longer sees it change.
    # The ibexa:behat:test-siteaccess fixture command does exactly that; it needs to switch to
    # SiteAccessServiceInterface::getCurrent() before this scenario can pass again.
    @broken
    Scenario: Commands use the default siteaccess if not specified
        When I run a console script without specifying a siteaccess
        Then it is executed with the default one

    Scenario: Commands use the siteaccess specified as with --siteaccess
        Given that there is a siteaccess that is not the default one
         When I run a console script with it
         Then I expect it to be executed with it
