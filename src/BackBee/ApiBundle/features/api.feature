@api
Feature: xx
    In xx
    As xx
    I want xx

    Background:
        Given I am logged in with role "ROLE_API_USER"

    Scenario: xx
        When I go to "/rest/2/classcontent-category"
        Then the response should contain json:
       """
         [{"id":"article","name":"Article","contents":[{"visible":true,"label":"Article","description":"An article contains a title, an author, an abstract, a primary image and a body","type":"Article\/Article"},{"visible":false,"label":null,"description":null,"type":"Article\/Body"},{"visible":true,"label":"Latest Articles","description":"List the latest articles","type":"Article\/LatestArticle"},{"visible":false,"label":"Article Container","description":"Automated article listing","type":"Article\/ArticleContainer"},{"visible":false,"label":"Article Container","description":"Automated article listing","type":"Article\/ListArticleContainer"},{"visible":true,"label":"Quote","description":"Quote","type":"Article\/Quote"},{"visible":false,"label":"Linked articles","description":"A block that display linked articles. Contains: ContentSet, Article","type":"Article\/Related"},{"visible":false,"label":"Linked article container","description":"A block that display linked article. Contains: ContentSet, Article","type":"Article\/RelatedContainer"},{"visible":false,"label":"Autoblock","description":"Automated content listing","type":"Block\/AutoBlock"},{"visible":true,"label":"Paragraph","description":"Paragraph","type":"Text\/Paragraph"}]},{"id":"block","name":"Block","contents":[{"visible":true,"label":"Column divider","description":"Column divider","type":"Block\/ColumnDivider"}]},{"id":"container","name":"Container","contents":[{"visible":false,"label":"One column","description":null,"type":"Container\/OneColumn"}]},{"id":"home","name":"Home","contents":[{"visible":true,"label":"Home article container","description":"Home article container","type":"Home\/HomeArticleContainer"},{"visible":false,"label":"home container","description":"","type":"Home\/HomeContainer"},{"visible":true,"label":"Slider","description":"Slider containing image, text and link slides","type":"Home\/Slider"}]},{"id":"media","name":"Media","contents":[{"visible":true,"label":"Clickable Thumbnail","description":"A clickable media image","type":"Media\/ClickableThumbnail"},{"visible":true,"label":"Iframe","description":"A block video","type":"Media\/Iframe"},{"visible":true,"label":"Media image","description":"A media image","type":"Media\/Image"},{"visible":true,"label":"Pdf file","description":"A pdf file","type":"Media\/Pdf"}]},{"id":"social","name":"Social","contents":[{"visible":true,"label":"Facebook block","description":"Facebook block","type":"Social\/Facebook"},{"visible":true,"label":"Twitter block","description":"Twitter block","type":"Social\/Twitter"}]}]
       """

