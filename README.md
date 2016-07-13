# yams
Yet another monitoring service...

### Why do we need another monitoring service?
YAMS was built to run on as many Linux based operating machines, as quickly as possible.  Using BASH and CURL, as well as pretty widespread common binaries for collecting data, you can gather information about your servers from just a single script.

### How does it work?
YAMS runs a series of informational queries against the system, parses the response, and sends a JSON document using curl to a consumer.  The informational queries gives information like, disk/memory utilization, hardware chassis information, and lastly, MySQL server information - global variables and global status and replication information.

### Doesn't [choose one: Nagios/icinga/vividcortex/zabbix/PMM] do this already?
Well, yes.. but if you want to be able to quickly deploy a deep information gathering tool across a lot of systems without having to install agents, this is one way to do it.  I found that for the purpose I needed it for (cross-datacenter/internal org machine analytics) and for the time frame I wanted that information (yesterday), this was the fastest way to get what I needed without having to depend on multiple teams to approve and install software.

### What do I need to run?
You need to do the following a linux client that you want to gather information from, a webserver running PHP, a MySQL database.

### Your code is ugly and full of bugs
That's why I posted it here - so you can fix them for me.. Thanks!  But seriously, this is an internal tool I use and I will accept all feedback/bug requests.

### Why did you pick MySQL/PHP/etc?
I wrote this in a weekend - I picked the things I was familiar with, however, it is a project of mine to port each of the components to other languages.  But feel free to go nuts and submit pull requests.

### How does the analytics piece work?
Once you have all the information in the MySQL database, you can either query it directly for information if you are a SQL ninja, or you can use the metric_export/import and import your data into influxDB.  I use grafana for visualizations.

### How large does the database grow?
That's the next thought - IME, I have a little over 100 hosts, pushing information once a day - for 3 MySQL instance per host.  There are some tricks done that help keep duplicated information not in the database, however, there is more work to be done here.  For 6 months of running, the database is just over 1GB. But, YMMV.
 
### Addendum

The list of binaries it uses are below:
- bash
- curl
- which
- echo
- hash
- dmidecode [optional]
- awk
- uniq
- sed
- uname
- cat
- lsb_release [optional]
- free
- grep
- df
- ip addr
- netstat
- mysql [optional]

