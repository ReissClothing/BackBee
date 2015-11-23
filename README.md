This repo is a complete migration of BackBee to Symfony framework.

#It's in a pre alpha state so please don't review yet. I'd like to have everything working, and once everything is working, we can focus on detaills such as docblocks, typos etc. but before that I'd like to have all the missing tasks done.


This is still a WIP and any help will be more than welcome.

##Current status
In short, everything more or less works, but it needs that final touch that might (or might not) open a Pandora box.

If you wish to colaborate please open an issue stating the task you'd like to perform.

There are quite a los @todo's in the code that need fixing, also there are this issues:

##Urgent
In BB twig throws ``pre`` and ``post`` render events, we should wrap twig to throw them too.

The whole front end setup needs to be done in a proper way, at least file locations and includes. So far it more or less works, but I'm not an expert at FE flow so this need to be double checked.

Make BB annotations in controllers work.

Check how API bundle should catch exceptions and return them: probably a format listener?

If authentication fails FE doesn't display error.

Security is implemented with a mix or voters and acl permissions, but I think a simpler security configuration would work too as I don't see where to assign fine grain permissions to users.

##Not so urgent:
Behat and phpspecs.

Move repos to CoreDomainBundle and add interfaces in CoreDomain fro those repos.

Move away from annotations: they're evil.

Content classes are generated on the fly, this I believe should be just a single class with different config parameters

Implement installation as BB has.

##Installation
Restore sample db ``bb-symfony.dump.bz2`` located in this repo into your db.

Configure db parameters in ``app/config/parameters.yml``

Clone this repo and do a ``composer install``

Edit ``BackBee\WebBundle\Renderer\AbstractRenderer.php`` line 537 and change it according to your setup.

#####IMPORTANT, your php.ini should have (this is because we include classes generated from strings, something that will probably change in near future):

#####allow_url_include=1

Login pressing ctrl + alt + b

username: admin

pswd: admin

###Notes: 
To make things easier for now, security uses in memory user provider.