docker run -p 8000:80 -it ^
    -p 5432:5432 -it ^
    --rm ^
    -v %cd%:/var/www/html ^
    --name apiphp ^
    apiphp
