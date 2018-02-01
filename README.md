# IQnection SilverStripe Algolia Site Search
Site search page powered by Algolia (requires Algolia account)

## Installation
- Upload files and perform a dev/build.
- In SilverStripe admin, create a new "Algolia Search Results Page" page.

## Setup
- Edit your new page, and click on the "Algolia Settings" tab.
- Fill in the first 4 fields with values obtained from your Algolia "API Keys" page.
- If you are searching more than one index, you may enter them in the "Additional Indecies" textarea.
- If you are using the Community (free) version of Algolia, you must leave the "Community Version" option checked.

## Indexing
- To run an initial index, go to your search page's URL, and add "/BuildIndex" to the end:
- {Search Page Full URL}/BuildIndex
- If the indexing process runs successfully, you can set up a cron job to run as frequently as you need.  For the Community version, once daily is recommended.
- cron command: wget "{Search Page Full URL}/BuildIndex" >/dev/null 2>&1

## Adding WordPress to Search
- Install the Algolia plugin for WordPress.
- Once you've activated the Algolia plugin and run your initial index, log into Algolia and get the name of the new WordPress index.
- Add this index name to the "Additional Indecies" section in your SilverStripe's page "Algolia Settings" tab.


