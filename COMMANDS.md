# ZTE OLT Telnet Commands Reference

This document provides a comprehensive list of Telnet commands for ZTE OLT (C300/C320/C600/ZXA10) used in this provisioning system.

## Table of Contents
1. [Connection Commands](#connection-commands)
2. [Show Commands](#show-commands)
3. [ONU Provisioning Commands](#onu-provisioning-commands)
4. [Configuration Commands](#configuration-commands)
5. [Maintenance Commands](#maintenance-commands)

## Connection Commands

### Login
```
Username: <username>
Password: <password>
```

### Enter Configuration Mode
```
configure terminal
# or
conf t
```

### Exit Configuration Mode
```
exit
```

### Exit to Main Mode
```
end
```

## Show Commands

### System Information
```
show version
show system running-time
show system uptime
show system resource
show cpu
show memory
```

### ONU Discovery and Status
```
# Show all unregistered ONUs
show gpon onu uncfg

# Show ONU state on specific PON port
show gpon onu state gpon-onu_1/1

# Show ONU detail information
show gpon onu detail-info gpon-onu_1/1:1

# Show all registered ONUs
show gpon onu profile

# Show ONU by serial number
show gpon onu by-sn ZNTS12345678
```

### PON Port Information
```
# Show PON port configuration
show interface gpon-onu_1/1

# Show PON port statistics
show interface gpon-onu_1/1 counter

# Show PON port optical info
show pon power-att gpon-onu_1/1
```

### ONU Optical Information
```
# Show ONU optical power
show gpon remote-onu optical-info gpon-onu_1/1:1

# Show ONU measured optical power
show gpon remote-onu measure-result gpon-onu_1/1:1

# Show ONU distance
show gpon remote-onu distance gpon-onu_1/1:1
```

### Service Port Information
```
show running-config interface gpon-onu_1/1:1
show service-port 100
show service-port all
```

### VLAN Information
```
show vlan 100
show vlan summary
show vlan port gpon-onu_1/1:1
```

## ONU Provisioning Commands

### Step 1: Register ONU
```
configure terminal
interface gpon-onu_1/1
onu 1 type F601 sn ZNTS12345678
exit
```

### Step 2: Configure TCONT
```
interface gpon-onu_1/1:1
tcont 1 profile DATA
exit
```

### Step 3: Configure GEM Port
```
interface gpon-onu_1/1:1
gemport 1 name Gem1 tcont 1
exit
```

### Step 4: Create Service Port
```
service-port 100 gpon-onu_1/1:1 gemport 1 user-vlan 100 vlan 100 svlan 100 vport 1
```

Or with VLAN translation:
```
service-port 100 gpon-onu_1/1:1 gemport 1 user-vlan 100 vlan 100 svlan 100 vport 1 tag-transform translate-and-add inner-vid 10 outer-vid 100
```

### Step 5: Configure WAN (PPPoE)
```
interface gpon-onu_1/1:1
pppoe 1 nat enable user customer@isp password secretpass
pppoe 1 ip-host 1 respond-ping enable
exit
```

### Step 6: Configure WiFi
```
interface gpon-onu_1/1:1
wifi enable
ssid Customer_WiFi
wpa2 enable
wpa2 password WiFiPassword123
exit
```

### Complete Provisioning Script
```
configure terminal
!
interface gpon-onu_1/1
onu 1 type F601 sn ZNTS12345678
!
interface gpon-onu_1/1:1
tcont 1 profile DATA
gemport 1 name Gem1 tcont 1
pppoe 1 nat enable user customer@isp password secretpass
pppoe 1 ip-host 1 respond-ping enable
wifi enable
ssid Customer_WiFi
wpa2 enable
wpa2 password WiFiPassword123
exit
!
service-port 100 gpon-onu_1/1:1 gemport 1 user-vlan 100 vlan 100 svlan 100 vport 1
!
end
```

## Configuration Commands

### TCONT Profiles
```
# Create TCONT profile
profile tcont DATA type 1 fixed 5000 assured 10000 maximum 50000
profile tcont VOICE type 3 assured 2000 maximum 5000

# Show TCONT profiles
show profile tcont
```

### Traffic Tables
```
# Create traffic table
traffic-table ip 10 name Data cir 50000 cbs 1000000 pir 1000000 pbs 2000000

# Show traffic tables
show traffic-table ip
```

### VLAN Configuration
```
# Create VLAN
vlan 100
name Customer_VLAN
exit

# Add port to VLAN
interface vlan 100
switchport gpon-onu_1/1:1
exit

# Show VLANs
show vlan 100
```

### Interface Configuration
```
# Configure interface
interface gpon-onu_1/1:1
description "Customer: John Doe"
no shutdown
exit
```

## Maintenance Commands

### Reboot ONU
```
# Reboot specific ONU
reboot gpon-onu_1/1:1

# Reboot with confirmation
reboot gpon-onu_1/1:1 confirm
```

### Factory Reset ONU
```
restore factory gpon-onu_1/1:1
```

### Delete ONU
```
configure terminal
interface gpon-onu_1/1
no onu 1
exit
```

### Disable/Enable ONU
```
# Disable ONU
interface gpon-onu_1/1:1
shutdown
exit

# Enable ONU
interface gpon-onu_1/1:1
no shutdown
exit
```

### Reset ONU Configuration
```
# Reset specific configuration
interface gpon-onu_1/1:1
no pppoe 1
no wifi
exit
```

## Diagnostic Commands

### Ping
```
ping <ip-address>
ping gpon-onu_1/1:1
```

### Traceroute
```
traceroute <ip-address>
```

### ONU Loopback Test
```
interface gpon-onu_1/1:1
loopback test
```

### ONU Alarm/Events
```
show gpon alarm history gpon-onu_1/1:1
show gpon event gpon-onu_1/1:1
```

## Batch Operations

### Multiple ONU Configuration
```
configure terminal
!
interface gpon-onu_1/1
range onu 1 to 10
  type F601 sn auto
  exit
!
interface range gpon-onu_1/1:1-10
  tcont 1 profile DATA
  gemport 1 name Gem1 tcont 1
  exit
!
end
```

### Copy Configuration
```
# Save configuration
write memory
# or
copy running-config startup-config

# Show saved configuration
show startup-config
```

## SNMP Configuration Commands

### Enable SNMP
```
snmp-server community public ro
snmp-server community private rw
snmp-server location "ISP Data Center"
snmp-server contact "admin@isp.com"
```

### SNMPv3 Configuration
```
snmp-server group admin v3 priv
snmp-server user admin admin v3 auth sha authpass priv aes privpass
```

## System Commands

### Save Configuration
```
write
# or
write memory
# or
copy running-config startup-config
```

### Reboot System
```
reload
# or
reload at <time>
```

### Show Running Configuration
```
show running-config
show running-config interface gpon-onu_1/1:1
```

### Debug Commands
```
# Enable debugging
debug gpon onu gpon-onu_1/1:1

# Disable debugging
no debug gpon onu gpon-onu_1/1:1

# Show debug information
show debug
```

## Output Parsing Examples

### Parse Unregistered ONUs
Sample output:
```
show gpon onu uncfg

  OnuIndex    Sn                Type
  ----------  ----------------  -----
  gpon-onu_1/1/1  ZNTS12345678      F601
  gpon-onu_1/1/2  ZNTS87654321      F660
```

### Parse ONU Status
Sample output:
```
show gpon onu state gpon-onu_1/1

  OnuId  AdminState  OMCCState  O7State  PhaseState
  -----  ----------  ---------  -------  ----------
  1      enable      enable     up       working
  2      enable      enable     down     losing
```

### Parse Optical Info
Sample output:
```
show gpon remote-onu optical-info gpon-onu_1/1:1

  Temperature: 45 C
  Vcc: 3.30 V
  Tx Bias: 12.00 mA
  Tx Power: 2.50 dBm
  Rx Power: -23.50 dBm
```

## Error Codes

Common error responses:
- `%Error 1: ONU already exists` - ONU with this ID is already registered
- `%Error 2: Invalid parameter` - Wrong command syntax
- `%Error 3: SN already exists` - Serial number already in use
- `%Error 4: ONU not found` - ONU ID doesn't exist
- `%Error 5: Service port conflict` - Service port ID already used

## Notes

1. Replace `1/1` with your actual slot/port numbers
2. Replace `ZNTS12345678` with actual ONU serial number
3. Commands may vary slightly between OLT models
4. Always save configuration after changes
5. Use `?` for command help
6. Press `Tab` for command completion
