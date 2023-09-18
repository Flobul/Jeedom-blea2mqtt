#!/bin/bash
  BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
  export PATH=$PATH:/opt/homebrew/bin/

  PASSWD="$1"
  THISUSER="$2"

  ######################### INCLUSION LIB ##########################
  wget https://raw.githubusercontent.com/Flobul/dependance.lib/master/dependance.lib -O $BASEDIR/dependance.lib &>/dev/null
  PLUGIN="blea2mqtt"
  . ${BASEDIR}/dependance.lib
  ##################################################################
  TIMED=1
  
  currentUser=$( echo "show State:/Users/ConsoleUser" | scutil | awk '/Name :/ { print $3 }' )
  uid=$(id -u "$currentUser")

  if [ ! -d "/tmp/jeedom/${PLUGIN}" ]; then
    mkdir /tmp/jeedom/${PLUGIN}
  fi
  
  pre
  
  try sudo sh -c "cat > /tmp/jeedom/launch_blea2mqtt.sh" << EOL
#!/bin/bash
echo "${PASSWD}" | sudo -S /usr/local/bin/yarn --cwd ${BASEDIR}/blea2mqtt start 
EOL

  try sudo mv -f /tmp/jeedom/launch_blea2mqtt.sh ${BASEDIR}/
  try sudo chmod +x ${BASEDIR}/launch_blea2mqtt.sh
  try sudo chown ${THISUSER}:$(id -gn ${THISUSER}) ${BASEDIR}/launch_blea2mqtt.sh

  step 70 "Création fichier de configuration"
  sudo chmod +w ${BASEDIR}/blea2mqtt/.env

  if [ -e "/tmp/jeedom/git.blea2mqtt.plist" ]; then
      sudo rm -f /tmp/jeedom/git.blea2mqtt.plist
  fi

  try sudo sh -c "cat > /tmp/jeedom/git.blea2mqtt.plist" << EOL
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>EnvironmentVariables</key>
    <dict>
        <key>PATH</key>
        <string>/opt/homebrew/bin:/opt/homebrew/sbin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/Library/Apple/usr/bin:</string>
    </dict>
    <key>Label</key>
    <string>git.blea2mqtt</string>
    <key>ServiceDescription</key>
    <string>blea2mqtt</string>
	<key>ProgramArguments</key>
	<array>
        <string>${BASEDIR}/launch_blea2mqtt.sh</string>
	</array>
    <key>RunAtLoad</key>
    <true/>
    <key>Nice</key>
    <integer>2</integer>
    <key>LowPriorityBackgroundIO</key>
    <false/>
    <key>LowPriorityIO</key>
    <false/>
    <key>ServiceDescription</key>
    <string>blea2mqtt</string>
    <key>StandardErrorPath</key>
    <string>/tmp/jeedom/git.blea2mqtt.stderr</string>
    <key>StandardOutPath</key>
    <string>/tmp/jeedom/git.blea2mqtt.stdout</string>
    <key>UserName</key>
    <string>${THISUSER}</string>
    <key>GroupName</key>
    <string>$(id -gn ${THISUSER})</string>
    <key>WorkingDirectory</key>
    <string>${BASEDIR}/blea2mqtt</string>
</dict>
</plist>
EOL

    if [ -e "/tmp/jeedom/blea2mqtt/git.blea2mqtt.stderr" ]; then
        sudo rm -f /tmp/jeedom/blea2mqtt/git.blea2mqtt.stderr
    fi
    if [ -e "/tmp/jeedom/blea2mqtt/git.blea2mqtt.stdout" ]; then
        sudo rm -f /tmp/jeedom/blea2mqtt/git.blea2mqtt.stdout
    fi

    sudo /bin/launchctl bootout gui/$(id -u $( ls -l /dev/console | awk '{print $3}' )) /Library/LaunchDaemons/git.blea2mqtt.plist
    sudo /bin/launchctl remove /Library/LaunchDaemons/git.blea2mqtt.plist
    try sudo cp -f /tmp/jeedom/git.blea2mqtt.plist /Library/LaunchDaemons/
    try sudo chmod 644 /Library/LaunchDaemons/git.blea2mqtt.plist

    step 80 "Installation yarn de blea2mqtt"
    try sudo /usr/local/bin/yarn config set ignore-engines true
    cd ${BASEDIR}/blea2mqtt
    try sudo /usr/local/bin/yarn

post