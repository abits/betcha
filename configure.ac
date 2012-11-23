# -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.68])
AC_INIT([FULL-PACKAGE-NAME], [VERSION], [BUG-REPORT-ADDRESS])
AC_CONFIG_SRCDIR([www])

# Checks for programs.

# localize
consoleuser=`whoami`
wwwuser=apache
php=`which php`

# Checks for library functions.

AC_SUBST(consoleuser)
AC_SUBST(wwwuser)
AC_SUBST(php)
AC_CONFIG_FILES([Makefile])
AC_OUTPUT