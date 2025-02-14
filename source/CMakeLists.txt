cmake_minimum_required(VERSION 3.22)
project(skud_system)

# Include libraries
include_directories("/usr/include/sqlite.h")
link_directories("/usr/lib/x86_64-linux-gnu/libsqlite3.so")

# Make library
add_library(lib STATIC ${CMAKE_CURRENT_SOURCE_DIR}/lib.cpp)

# Executable
add_executable(skud_system ${CMAKE_CURRENT_SOURCE_DIR}/main.cpp)

# Link libraries
target_link_libraries(skud_system sqlite3)
