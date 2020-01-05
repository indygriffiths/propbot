# Slack Property Finder Bot

A handy bot to alert you whenever there is a new Trade Me residential or rental listed that matches your specifications. Designed to be run periodically as a cron job,

* Uses the Trade Me API to grab new rental or residential properties that have been recently listed.
* Checks if fibre and VDSL are available by querying Chorus.
* Includes travel times to various locations if set.

![so pretty](http://i.imgur.com/nQe1RgQ.png "so pretty, lack of fibre is a bummer though")

**This program is not endorsed by Trade Me or Chorus in any shape or form**

## Requirements
* PHP 7.1+ with the `json` extension enabled
* Trade Me API Key [(register an application)](https://www.trademe.co.nz/MyTradeMe/Api/RegisterNewApplication.aspx)
* Google Distance Matrix API Key [(get a key)](https://developers.google.com/maps/documentation/distance-matrix/start#get-a-key)

## Configuration

By default the configuration variables are loaded from environment variables, or they can be loaded from a file located in `$HOME/.propbot/config`.

If you want to use a different directory for loading the config file, specify the `--config-dir` option with a full path to the folder to use.

### Travel times

The bot can use the Google Maps Distance Matrix API to work out the rough travel time to certain addresses. Google requires you to enable Billing on the API project that your API tokens refer to.

Addresses can be defined in the `GOOGLE_ADDRESSES` environment variable. The variable is a JSON array with a `name` and `address` property.

```bash
GOOGLE_ADDRESSES="[{\"name\": \"Work\", \"address\": \"123 Fake Street, Wellington 6011, New Zealand\"}]"
```

### Environment variables

```bash
TRADEME_CONSUMER_KEY=""
TRADEME_CONSUMER_SECRET=""

SLACK_WEBHOOK_URL=""
SLACK_WEBHOOK_USERNAME="Property Finder"
SLACK_WEBHOOK_CHANNEL="#houses"

# Used for working out travel times to certain addresses
GOOGLE_KEY=""
GOOGLE_ADDRESSES=""
GOOGLE_TRANSPORT_METHOD="transit"
GOOGLE_DISTANCE_UNIT="metric"
```

## Installation
* `composer install`
* `bin/propbot`

## Usage

* On first run of a command any listings within the last hour are fetched. Subsequent runs of the command will fetch properties listed since the last run time.
* Use `--help` to list all available arguments as these are used for filtering the listings fetched from Trade Me.

Post _any_ recent rental property listings in New Zealand to Slack:

```bash
bin/propbot find:rentals
```

Post recent rental property listings to Slack with 2-5 bedrooms that allow pets:

```bash
bin/propbot find:rentals --pets-ok true --bedrooms-min 2 --bedrooms-max 5
```

Post recent property listings up to $500k in the greater Wellington region to Slack:

```bash
bin/propbot find:properties --region 15 --price-max 500000
```

## Localities / Districts / Suburbs

The Trade Me API uses numeric IDs for representing localities, districts, and suburbs. These can be fetched from the following endpoint:

```
https://api.trademe.co.nz/v1/Localities.json
```

For example:
 * Wellington at the locality (region level) has a LocalityID of 15.  (use `--region`)
 * Upper Hutt within that locality has a DistrictID of 45.  (use `--district`)
 * Clouston Park within that district has a SuburbID of 3067. (use `--suburb`)
 
Given that showing all properties for a locality can be quite broad, you can comma-separate IDs when filtering results to only search within those locations.

For example, if I only want to see places for Heretaunga and Clouston Park within Upper Hutt, I can use this to only search those suburbs:

```bash
bin/propbot find:rentals --suburb 3067,925
```

If you want to look up what the IDs is you can use the `lookup:locations` command to search for locations. For example, if I wanted some Dunedin suburbs:

```bash
bin/propbot lookup:locations --name "Dunedin" --name "Andersons Bay" --name "Tainui" --name "Waverley" --name "Brockville" --name "Caversham" --name "Shiel Hill" --name "Concord" --name "Forbury" --name "Kew" --name "Maori Hill" --name "Maryhill"

Fetching Trade Me localities
+--------------------------------------+-------------+-------------+-----------+
| Location                             | Locality ID | District ID | Suburb ID |
+--------------------------------------+-------------+-------------+-----------+
| Otago / Dunedin                      |             | 71          |           |
| Taranaki / South Taranaki / Waverley |             |             | 1034      |
| Otago / Dunedin / Andersons Bay      |             |             | 3031      |
| Otago / Dunedin / Brockville         |             |             | 2013      |
| Otago / Dunedin / Caversham          |             |             | 1809      |
| Otago / Dunedin / Concord            |             |             | 2378      |
| Otago / Dunedin / Forbury            |             |             | 3535      |
| Otago / Dunedin / Kew                |             |             | 1832      |
| Otago / Dunedin / Maori Hill         |             |             | 1942      |
| Otago / Dunedin / Maryhill           |             |             | 1906      |
| Otago / Dunedin / Shiel Hill         |             |             | 1747      |
| Otago / Dunedin / Tainui             |             |             | 1418      |
| Otago / Dunedin / Waverley           |             |             | 3028      |
| Southland / Invercargill / Kew       |             |             | 2080      |
| Southland / Invercargill / Waverley  |             |             | 3475      |
| Southland / Southland / Waverley     |             |             | 3138      |
+--------------------------------------+-------------+-------------+-----------+

To use these in your find:* commands, use the following arguments:
   --district "71" --suburb "1034,3031,2013,1809,2378,3535,1832,1942,1906,1747,1418,3028,2080,3475,3138"
```

Even though the list includes results from Southland and Taranaki, the results will still be limited to Dunedin as I've searched for `Dunedin` which has returned the Dunedin district ID.
