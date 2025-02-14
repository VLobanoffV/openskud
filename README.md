# openskud
Open source working time monitoring system based on RFID cards for warehouse.

## You can deploy a container using docker-compose, note that you need to replace some values ​​in docker-compose.yml:
```
devices:
    - '/dev/input/by-id/usb-Sycreader_RFID_Technology_Co.__Ltd_SYC_ID_IC_USB_Reader_08FF20140315-event-kbd:/dev/input/by-id/usb-Sycreader_RFID_Technology_Co.__Ltd_SYC_ID_IC_USB_Reader_08FF20140315-event-kbd'
```

```
volumes:
    - '/opt/openskud/skud.db:/app/skud.db'
```
