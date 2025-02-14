#include "lib.cpp"

std::map<int, char> key_map = {
    {2, '1'}, {3, '2'}, {4, '3'}, {5, '4'}, {6, '5'}, {7, '6'}, {8, '7'}, {9, '8'}, {10, '9'}, {11, '0'},
    {28, '\n'} 
};

int main() {

    sqlite3* db;
    int rc;

    rc = sqlite3_open("/app/skud.db", &db);
    if (rc != SQLITE_OK) {
        std::cerr << "Can't open database: " << sqlite3_errmsg(db) << std::endl;
        return 1;
    } else {
        std::cout << "Opened database successfully!" << std::endl;
    }
	
    const char* device = "/dev/input/by-id/usb-Sycreader_RFID_Technology_Co.__Ltd_SYC_ID_IC_USB_Reader_08FF20140315-event-kbd";
    int fd = open(device, O_RDONLY);

    if (fd == -1) {
        std::cerr << "Не удалось открыть устройство: " << device << std::endl;
        return 1;
    }

    struct input_event ie;
    std::string card_id;

    std::cout << "Ожидание данных с RFID-считывателя..." << std::endl;

    while (true) {
        ssize_t bytes = read(fd, &ie, sizeof(struct input_event));
        if (bytes < (ssize_t)sizeof(struct input_event)) {
            std::cerr << "Ошибка чтения события" << std::endl;
            break;
        }

        if (ie.type == EV_KEY && ie.value == 1) {
            auto it = key_map.find(ie.code);
            if (it != key_map.end()) {
                char key = it->second;
                if (key == '\n') { 
                    std::cout << "Прочитанный ID карты: " << card_id << std::endl;
                        if (logVisit(db, std::stoi(card_id))) {
        			        std::cout << "Visit logged successfully." << std::endl;
    		  	        } else {
        			        std::cerr << "Failed to log visit." << std::endl;
   		 		        }
                    card_id.clear();
                } else {
                    card_id += key;
                }
            }
        }
    }

    sqlite3_close(db);
    close(fd);
    return 0;
}

