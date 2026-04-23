#!/bin/bash

a2dismod mpm_event mpm_worker 2>/dev/null
a2enmod mpm_prefork

apache2-foreground