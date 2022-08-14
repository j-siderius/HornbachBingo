# Hornbach Bingo
Hornbach bingo application written in PHP. Generates a random bingo grid with (in-stock) products from Hornbach. Multiple people can join one team. Hints can be requested about the location of the product. Time limit counts down.

## Inspiration
I looove the Hornbach :)
<br>(that's about it)

## Frontend
*TODO*

## Backend / API
All controlling is done using the PHP-based API with the following endpoints:

### Controller
- *api/session.php?new={name}*
  - {name} = session name
  - returns 201 + {sessionID, sessionName}
  - returns 400 when there is already a sessionID saved
- *api/session.php?get*
  - returns 200 + {sessionID, sesssionName, sessionRunning, sessionStartTime, sessionProducts, sessionFoundProducts, sessionHints}
  - returns 404 when the saved sessionID does not exist in the DB
  - returns 400 when there is no sessionID saved
- *api/session.php?join={name}&pin={pin}*
  - {name} = session name
  - {pin} = 4-digit pincode
  - returns 200 + {sessionID, sessionName}
  - returns 404 when the {name} does not exist in the DB
  - returns 401 when the {pin} does not match the pin in the DB

### Product
- *api/product.php?id={id}*
  - {id} = product ID
  - returns 200 + {id, productName, productPicture}
  - returns 404 when no valid product ID is passed
  - returns 404 when no {id} is set
- *api/product.php?checkproduct={id}&ean={ean}*
  - {id} = product ID
  - {ean} = product EAN code
  - returns 200 + {sessionID, sessionFoundProducts}
  - returns 400 when the session has expired
  - returns 404 when the {id} is not in the sessionProducts
  - returns 400 when the {id} is already in sessionFoundProducts
  - returns 400 when the {ean} is not correct
  - returns 400 when there is no sessionID saved
  - returns 404 when no {id} or {ean} is set
- *api/product.php?hintproduct={id}*
  - {id} = product ID
  - returns 200 + {sessionID, hintsUsed, productID, productLocation}
  - returns 400 when the session has expired
  - returns 400 when there is no sessionID saved
  - returns 404 when no {id} is set

## Scraping data
All product information was scraped from the Hornach website using a Python-based scraping tool. It used the Selenium library to emulate a browser. Approximately 4500 products were scraped and archived.

## Sorting / Updating data
All data was put into a MySQL database. To ensure fair play, only products which were in ample supply (>10 available in store) were added to the database. The database can be updated using a Python utility script which checks and updates all products and their availabilty.