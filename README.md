This repo is a complete migration of BackBee to Symfony framework.

#It's in a pre alpha state so please don't review yet. I'd like to have everything working, and once everything is working, we can focus on detaills such as docblocks, typos etc. but before that I'd like to have all the missing tasks done.


This is still a WIP and any help will be more than welcome.

##Current status
In short, everything more or less works, but it needs that final touch that might (or might not) open a Pandora box.

If you wish to colaborate please open an issue stating the task you'd like to perform.

###There are quite a lot of @todo's pending in the code that need fixing, also there are this issues:

##Urgent
In BB twig throws ``pre`` and ``post`` render events, we should wrap twig to throw them too.

The whole front end setup needs to be done in a proper way, at least file locations and includes. So far it more or less works, but I'm not an expert at FE flow so this need to be double checked.

Make BB annotations in controllers work, or even better, achieve same functionality without annotations. 

Check how API bundle should catch exceptions and return them: probably a format listener?

If authentication fails FE doesn't display error.

Security is implemented with a mix or voters and acl permissions, but I think a simpler security configuration would work too as I don't see where to assign fine grain permissions to users.

Authentication is done via standard symfony cookie/session, so there is no need to use ``X-API-KEY`` and ``X-API-SIGNATURE``, unless we want a remote API. Fe should be adapted to this scenario. 

Fixtures 

##Not so urgent:
Behat and phpspecs.

Move repos to CoreDomainBundle and add interfaces in CoreDomain fro those repos.

Move away from annotations: they're evil.

Content classes are generated on the fly, this I believe should be just a single class with different config parameters

Implement installation as BB has (including fixtures).

``Metadata`` class names are saved in some db fields, but the classes need to be moved to ``CoreDomain``, so a migration should be done to change ``MetaData\MetaData`` to ``CoreDomain\MetaData\MetaData``.

##Installation
Restore sample db ``bb-symfony.dump.bz2`` located in this repo into your db.

Configure db parameters in ``app/config/parameters.yml``

Clone this repo and run:
 
    composer install
    
    app/console doctrine:migrations:migrate
 
    app/console assets:install --symlink
 
    app/console assetic:dump

#####IMPORTANT, your php.ini should have (this is because we include classes generated from strings, something that will probably change in near future):

#####allow_url_include=1

Login pressing ctrl + alt + b

username: admin

pswd: admin

###Notes: 
To make things easier for now, security uses in memory user provider.

###Source structure:
CoreDomain: Core doamin classes, framework agnostic.

CoreDomainBundle: Integration of Core doamin classes into Symfony framework.

WebBundle: Anything that is used to display content on the frontend.

ApiBundle: All APi related stuff (this is used by js in web bundle).

StandardBundle: Same as BB standard bundle: an example implementation of a real site.

ToolbarBundle: Just gets the toolbar config. Original BB repo had a toolbar bundle too, not sure if this should be kept or moved to another bundle.