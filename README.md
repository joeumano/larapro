## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. 

## Clearing cache
sail artisan cache:clear
ddcache
sail artisan config:cache
sail artisan config:clear
sail artisan route:clear
sail artisan config:cache
sail artisan route:cache
sail artisan optimize

## Create new module
sail artisan module:make Contacts
sail artisan module:make-migration create_contacts_table contacts
sail artisan module:make-model Contact contacts
https://github.com/akaunting/laravel-module

## Updates

git diff --name-only b8080b96d7fc2384bd9716b3e1774c843f546300 c06fb95dfbec2a62e752c20c5d6cb771e98d90c4 > .diff-files.txt && npm run zipupdate