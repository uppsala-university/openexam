TEMPLATE = app
CONFIG += console
CONFIG -= app_bundle
CONFIG -= qt

SOURCES += main.cpp \
    options.cpp \
    task.cpp \
    application.cpp

HEADERS += \
    options.hpp \
    task.hpp \
    application.hpp

OTHER_FILES += \
    simulate.pro.user \
    client.txt

QMAKE_CXXFLAGS += -std=c++0x -pthread
LIBS += -pthread
