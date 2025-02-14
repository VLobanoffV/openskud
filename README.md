# openskud
Open source working time monitoring system based on RFID cards for warehouse.

### You can deploy a container using docker-compose, note that you need to replace some values ​​in docker-compose.yml:  
>Path to your rfid reader:
```
devices:
    - '/dev/input/by-id/usb-Sycreader_RFID_Technology_Co.__Ltd_SYC_ID_IC_USB_Reader_08FF20140315-event-kbd:/dev/input/by-id/usb-Sycreader_RFID_Technology_Co.__Ltd_SYC_ID_IC_USB_Reader_08FF20140315-event-kbd'
```

>Path to your sqlite3 database:
``` 
volumes:
    - '/opt/openskud/skud.db:/app/skud.db'
```
