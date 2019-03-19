## crontab -e

*/30    *       *       *       *       /sqlback.sh
*       3       *       *       0       /root/clearcache.sh
*       *       *       *       *       ~/crontab.sh
*       *       *       *       *       php /home/wwwroot/babycoin/e02b2943b3c6265b12da8f385f7cab75.php