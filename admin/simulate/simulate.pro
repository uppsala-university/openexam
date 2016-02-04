TEMPLATE = app
CONFIG += console
CONFIG -= app_bundle
CONFIG -= qt

SOURCES += main.cpp \
    options.cpp \
    task.cpp \
    application.cpp \
    result.cpp \
    output.cpp

HEADERS += \
    options.hpp \
    task.hpp \
    application.hpp \
    result.hpp \
    output.hpp

OTHER_FILES += \
    simulate.pro.user \
    clients.txt

QMAKE_CXXFLAGS += -std=c++0x -pthread
LIBS += -pthread
