# Getting started

To setup the project using docker.
Do the following steps:

* Copy the `.env.db.template` and set the new file's name to `.env.db`.
* duplicate all files here: phalcon-mvc/app/config/ that ends in .def.in and name them with just the .def
* Add correct credentials to external services and database in the new .def files.
* Create folder logs in project root.
* See to it that the application has read and write access to all files. For instance if your on a ubuntu or similar
  set owner and group to www-data.
* Imort database structure.
* Install simple saml by downloading from: https://simplesamlphp.org/download, extract and put folder in project root path.


Warning you will get weird error routing errors if the persmissions arn't set up right.
