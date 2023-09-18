#!/bin/bash
  echo "$1" > /tmp/pass.log
  BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
  UNAME=$(uname -s)
  PASSWD="${1}"
  GITHUB="${2}"

  if [ -z "$GITHUB" ]; then
    GITHUB="https://github.com/Flobul/blea2mqtt.git"
  fi

  goSudo() {
    if [ -n "$PASSWD" ]; then
      echo "$PASSWD" | sudo -S "$@"
    else
      "$@"
    fi
  }

  ######################### INCLUSION LIB ##########################
  wget https://raw.githubusercontent.com/Flobul/dependance.lib/master/dependance.lib -O $BASEDIR/dependance.lib &>/dev/null
  PLUGIN="blea2mqtt"
  . ${BASEDIR}/dependance.lib
  ##################################################################
  TIMED=1
  if [ ! -d "/tmp/jeedom/${PLUGIN}" ]; then
    mkdir /tmp/jeedom/${PLUGIN}
  fi
  pre

if [[ "$UNAME" == "Darwin" ]]; then

  step 10 "Vérification de brew"
  if [[ $(command -v brew) = "" ]]; then
    tryOrStop /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    echo "[ 19% ] : Installation de brew : [  OK  ]"
  else
    echo "[ 19% ] : Brew est installé sur ce système : $(/opt/homebrew/bin/brew -v | head -n1)"
  fi
  step 20 "Vérification des packages nécessaires"
  try /opt/homebrew/bin/brew install git

  step 30 "Vérification de node"
  tryOrStop /opt/homebrew/bin/brew install node

  step 40 "Vérification de yarn"
  tryOrStop /opt/homebrew/bin/brew install yarn

  step 50 "Récupération de blea2mqtt"
  if [ ! -d ${BASEDIR}/blea2mqtt ]; then
    goSudo git clone ${GITHUB}
    silent cd ${BASEDIR}/blea2mqtt
    echo "[ 59% ] : blea2mqtt est copié : [  OK  ]"
  else
    silent cd ${BASEDIR}/blea2mqtt
    goSudo git stash
    goSudo git pull ${GITHUB}
    echo "[ 59% ] : blea2mqtt est mis à jour : [  OK  ]"
  fi
  step 60 "Création du script de lancement de blea2mqtt"

#  try goSudo sh -c "cat > /tmp/jeedom/launch_blea2mqtt.sh" << EOL
##!/bin/bash
#echo "${PASSWD}" | sudo -S /usr/local/bin/yarn --cwd ${BASEDIR}/blea2mqtt start 
#EOL

  #try goSudo mv -f /tmp/jeedom/launch_blea2mqtt.sh ${BASEDIR}/
  #try goSudo chmod +x ${BASEDIR}/launch_blea2mqtt.sh

elif [[ $UNAME == "Linux" ]]; then

  wget https://raw.githubusercontent.com/Flobul/nodejs_install/main/install_nodejs.sh -O $BASEDIR/install_nodejs.sh &>/dev/null
  installVer='18' 	#NodeJS major version to be installed

  step 10 "Mise à jour APT"
  tryOrStop sudo apt-get update

  step 20 "Installation des packages nécessaires"
  cd ${BASEDIR};
  try sudo DEBIAN_FRONTEND=noninteractive apt-get install -y lsb-release build-essential apt-utils git curl gcc g++ make gpg

  step 30 "Installation de Yarn"
  try curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor > /etc/apt/trusted.gpg.d/yarn.gpg
  try echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
  tryOrStop sudo apt-get update
  try sudo DEBIAN_FRONTEND=noninteractive apt-get install -y yarn

  step 50 "Installation de Nodejs"
  . ${BASEDIR}/install_nodejs.sh ${installVer}

  step 70 "Récupération de blea2mqtt"

  if [ ! -d ${BASEDIR}/blea2mqtt ]; then
    tryOrStop sudo git clone ${GITHUB}
    cd blea2mqtt
  else
    cd blea2mqtt
    try sudo git stash
    tryOrStop sudo git pull ${GITHUB}
  fi
  silent sudo bash -c ./setup.sh

  step 80 "Création fichier de configuration"
  try sudo cp ${BASEDIR}/blea2mqtt/.env.example ${BASEDIR}/blea2mqtt/.env

  sudo bash -c "cat >> /tmp/jeedom/blea2mqtt.service" << EOL
  [Unit]
  Description=blea2mqtt

  [Service]
  Type=simple
  WorkingDirectory=${BASEDIR}/blea2mqtt
  ExecStart=/usr/bin/sudo /usr/bin/yarn --cwd ${BASEDIR}/blea2mqtt start
  User=root
  Group=root
  TimeoutStopSec=900
  TimeoutSec=900
  Restart=on-failure

  [Install]
  WantedBy=multi-user.target
EOL

  try sudo systemctl stop blea2mqtt.service
  try sudo mv /tmp/jeedom/blea2mqtt.service /etc/systemd/system/

  step 90 "Installation yarn de blea2mqtt"
  cd ${BASEDIR}/blea2mqtt
  try sudo yarn
fi

post