getalexa:
	wget -q http://s3.amazonaws.com/alexa-static/top-1m.csv.zip
	unzip top-1m.csv.zip

populatesqs: getalexa
	cat top-1m.csv | php domains2sqs.php

clean:
	rm composer.lock
	rm composer.phar
	rm -rf vendor
