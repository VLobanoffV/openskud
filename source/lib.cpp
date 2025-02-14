#include <fcntl.h>
#include <unistd.h>
#include <linux/input.h>
#include <map>
#include <iostream>
#include <sqlite3.h>
#include <string>
#include <sstream>
#include <iomanip>
#include <ctime>

std::string getCurrentDate() {
    std::time_t t = std::time(nullptr);
    std::tm* tm = std::localtime(&t);
    std::stringstream ss;
    ss << std::put_time(tm, "%d.%m.%Y");
    return ss.str();
}

std::string getCurrentTime() {
    std::time_t t = std::time(nullptr);
    std::tm* tm = std::localtime(&t);
    std::stringstream ss;
    ss << std::put_time(tm, "%H:%M:%S");
    return ss.str();
}

bool logVisit(sqlite3* db, int rfid_id) {
    sqlite3_stmt* stmt;
    std::string current_date = getCurrentDate();
    std::string current_time = getCurrentTime();

    std::string selectQuery = "SELECT id, time_in, time_out FROM visits WHERE rfid_id = ? AND date = ?;";
    int rc = sqlite3_prepare_v2(db, selectQuery.c_str(), -1, &stmt, nullptr);
    if (rc != SQLITE_OK) {
        std::cerr << "Failed to prepare select statement: " << sqlite3_errmsg(db) << std::endl;
        return false;
    }

    sqlite3_bind_int(stmt, 1, rfid_id);
    sqlite3_bind_text(stmt, 2, current_date.c_str(), -1, SQLITE_STATIC);

    rc = sqlite3_step(stmt);
    if (rc == SQLITE_ROW) {
        const char* time_in = reinterpret_cast<const char*>(sqlite3_column_text(stmt, 1));
        const char* time_out = reinterpret_cast<const char*>(sqlite3_column_text(stmt, 2));

        if (time_in != nullptr) {
            std::string updateQuery = "UPDATE visits SET time_out = ?, work_time = (julianday(time_out) - julianday(time_in)) * 24 * 60 WHERE id = ?";
            sqlite3_stmt* updateStmt;
            rc = sqlite3_prepare_v2(db, updateQuery.c_str(), -1, &updateStmt, nullptr);
            if (rc != SQLITE_OK) {
                std::cerr << "Failed to prepare update statement: " << sqlite3_errmsg(db) << std::endl;
                return false;
            }

            sqlite3_bind_text(updateStmt, 1, current_time.c_str(), -1, SQLITE_STATIC);
            sqlite3_bind_int(updateStmt, 2, sqlite3_column_int(stmt, 0));

            rc = sqlite3_step(updateStmt);
            if (rc != SQLITE_DONE) {
                std::cerr << "Failed to update visit: " << sqlite3_errmsg(db) << std::endl;
                return false;
            }
            std::cout << "Time out updated for rfid_id: " << rfid_id << std::endl;
        }
    } else if (rc == SQLITE_DONE) {
        std::string insertQuery = "INSERT INTO visits (date, rfid_id, first_name, last_name, time_in) "
                                  "SELECT ?, ?, first_name, last_name, ? FROM users WHERE rfid_id = ?;";
        sqlite3_stmt* insertStmt;
        rc = sqlite3_prepare_v2(db, insertQuery.c_str(), -1, &insertStmt, nullptr);
        if (rc != SQLITE_OK) {
            std::cerr << "Failed to prepare insert statement: " << sqlite3_errmsg(db) << std::endl;
            return false;
        }

        sqlite3_bind_text(insertStmt, 1, current_date.c_str(), -1, SQLITE_STATIC);
        sqlite3_bind_int(insertStmt, 2, rfid_id);
        sqlite3_bind_text(insertStmt, 3, current_time.c_str(), -1, SQLITE_STATIC);
        sqlite3_bind_int(insertStmt, 4, rfid_id);

        rc = sqlite3_step(insertStmt);
        if (rc != SQLITE_DONE) {
            std::cerr << "Failed to insert visit: " << sqlite3_errmsg(db) << std::endl;
            return false;
        }
        std::cout << "New visit record added for rfid_id: " << rfid_id << std::endl;
    }

    sqlite3_finalize(stmt);
    return true;
}
