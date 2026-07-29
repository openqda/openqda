#!/bin/bash -e
# SCRIPT TO SETUP OPENQDA USING DOCKER
#
# LAST UPDATED: JUL 2026
# REMOVE USELESS LINE ENDINGS WITH
# sed -i '' $'s/\r$//' this_install_script.sh
#
SCRIPT_VERSION="1.0"
SCRIPT_BUILD="65"
SCRIPT_SELF="${0}"
SCRIPT_HOME="$(pwd)"
SCRIPT_BASE_NAME=$(basename "$0")
SCRIPT_WEB_DIR="web"
SCRIPT_DOCKER_TEMPLATE="docker-compose.mac"
SCRIPT_DOCKER_BACKUP="docker-compose.backup"
SCRIPT_DOCKER_TARGET="docker-compose.yml"
SCRIPT_PRODUCT_NAME="OpenQDA"
SCRIPT_NAME="${SCRIPT_PRODUCT_NAME} SETUP SCRIPT DOCKER"
SCRIPT_PRESS_ANY_KEY="Press any key to continue or CTRL-C to abort"
SCRIPT_ENV_TEMPLATE=".env.example"
SCRIPT_ENV=".env"
SCRIPT_USE_COLOR=1
SCRIPT_EXECUTE_AUTOINSTALL=0
SCRIPT_USE_DEFAULTS=0
SCRIPT_PID_FRONTEND=0
SCRIPT_ALTCHA_HMAC_KEY="<your-hmac-key>"
SCRIPT_DB_PASSWORD_MYSQL="<db-password-mysql>"
SCRIPT_REVERB_HOST="<reverb-host-or-ip>"
SCRIPT_REVERB_PORT="<reverb-port>"
SCRIPT_DB_PASSWORD_MYSQL_DEFAULT="justadefaultpassword"
SCRIPT_REVERB_HOST_DEFAULT="0.0.0.0"
SCRIPT_REVERB_PORT_DEFAULT="8080"
SCRIPT_USER="undefined"
# SCRIPT_PRESS_CTRL_D="Press CTRL-D to stop"

# BASIC CHECK FOR REALLY RUNNING IN BASH
if [ -z "$BASH_VERSION" ]; then
	echo "ERROR: THIS SCRIPT NEEDS TO RUN IN A BASH SHELL"
	echo "PLEASE TRY EXECUTING THE FOLLOWING TO SUCCEED:"
	echo ""
	echo " bash ${SCRIPT_SELF}"
	echo ""
	echo "IF YOU RUN ON A MACOS AND MISS BASH, INSTALL IT"
	echo "THROUGH homebrew EXECUTING THE FOLLOWING COMMAND"
	echo "brew install bash"
	echo ""
	exit 1
fi

# MAKE SCRIPT FAIL IF WE ENCOUNTER A NON-ZERO RETURN CODE IN
# EITHER DIRECT COMMAND EXECUTION OR NON-ZERO RETURNS WITHIN
# PIPED COMMANDS
set -eou pipefail

# CONVENIENCE FUNCTION TO PROVIDE COLORIZED echo-OUTPUT
log() {
	if [[ $# == 0 ]]; then
		echo -e "" # NO ARGUMENTS GIVE EMPTY echoe AS NEW LINE
	else
		COLR="" # CONTAINTS THE ESCAPE SEQUENCE FOR SETTING TERMINAL COLOR MODE
		LFD=""  # ADDS OPTIONAL LINEFEED AT BEFORE AND AFTER OUTPUT CONTENT
		SPC=""  # ADDITIONAL SPACES ADDED BEFORE AND AFTER
		case $1 in
		"red") COLR="\033[31m" ;;
		"red-b") COLR="\033[1;5;31m" ;; # -b APPENDED WILL MAKE COLOR BLINK & BOLD
		"green") COLR="\033[32m" ;;
		"green-b") COLR="\033[1;5;32m" ;;
		"yellow") COLR="\033[33m" ;;
		"yellow-b") COLR="\033[1;5;33m" ;;
		"blue") COLR="\033[34m" ;;
		"blue-b") COLR="\033[1;5;34m" ;;
		"purple") COLR="\033[35m" ;;
		"purple-b") COLR="\033[1;5;35m" ;;
		"cyan") COLR="\033[36m" ;;
		"cyan-b") COLR="\033[1;5;36m" ;;
		"white") COLR="\033[37m" ;;
		"white-b") COLR="\033[1;5;37m" ;;
		"grey") COLR="\033[2;37m" ;;
		"grey-b") COLR="\033[2;5;37m" ;;
		"black") COLR="\033[30m" ;;
		"black-b") COLR="\033[1;5;30m" ;;
		"ok") COLR="\033[0;42;30m";LFD="\n";SPC="   " ;; # ok, attn, warn, info, hint & fatal USE INVERSE COLORED BACKGROUND
		"ok-b") COLR="\033[0;5;42;30m";LFD="\n";SPC="   " ;;
		"hint") COLR="\033[0;47;30m";LFD="\n";SPC="   " ;;
		"hint-b") COLR="\033[0;5;47;30m";LFD="\n";SPC="   " ;;
		"attn") COLR="\033[0;43;30m";LFD="\n";SPC="   " ;;
		"attn-b") COLR="\033[0;5;43;30m";LFD="\n";SPC="   " ;;
		"warn") COLR="\033[0;41;30m";LFD="\n";SPC="   " ;;
		"warn-c") COLR="\033[0;41;30m";LFD="";SPC="   " ;;
		"warn-b") COLR="\033[0;5;41;37m";LFD="\n";SPC="   " ;;
		"info") COLR="\033[0;44;37m";LFD="\n";SPC="   " ;;
		"info-b") COLR="\033[0;5;44;37m";LFD="\n";SPC="   " ;;
		"fatal") COLR="\033[0;45;30m";LFD="\n";SPC="   " ;;
		"fatal-b") COLR="\033[0;5;45;30m";LFD="\n";SPC="   " ;;
		*)
			echo -e "$1"
			return
			;; # IF ONLY CONTENT AS ARGUMENT AND NO COLOR INFO
		esac
		RST="\033[0m"                                     # RESETS ALL COLOR MODES ACTIVATED
		if [[ $SCRIPT_USE_COLOR -eq 1 ]]; then
			echo -e "${LFD}${COLR}${SPC}$2${SPC}${RST}${LFD}" # ECHOS SECOND ARGUMENT PASSED IN WITH COLOR FROM ARG 1
		else
			echo -e "${LFD}${SPC}$2${SPC}${LFD}"
		fi
	fi
}

# HELPER FUNCTION TO DETECT CTRL-C
script_ctrlc() {
	log ""
	log warn "ABORTED"
	log info "*** GOOD BYE! (CTRL-C) ***"
	exit 1 # THIS IS IMPORTANT!!!
}

# HELPER TO EXIT THE NICE WAY WITH AN ERROR
script_error() {
	log ""
	log warn "ERROR"
	log "SCRIPT FAILED TO EXECUTE SUCCESSFULLY. SEE ERROR ABOVE."
	log info "*** GOOD BYE! (ERROR) ***"
}

# HELPER TO ABORT THE SCRIPT PROGRAMMATICALLY
script_abort() {
	log ""
	log warn "ABORTED"
	log info "*** GOOD BYE! (EXITING) ***"
	exit 1
}

# HELPER TO EXIT THE SCRIPT PROGRAMMATICALLY
script_exit() {
	log ""
	log "SCRIPT EXIT BY USER."
	log info "*** GOOD BYE! (EXITING) ***"
	exit 0
}

# HELPER TO KILL ALL STARTED SUBPROCESSES BEFORE EXITING SCRIPT
script_kill() {
	log fatal "EXIT: TERMINATED ALL SUB-/BACKGROUND-PROCESSES, STARTED IN CONTEXT OF THE SCRIPT."
	# THIS WILL KILL ALL BACKGROUND JOBS STARTED IN CONTEXT OF THIS SCRIPT
	kill 0
}

# HELPER TO ASK FOR CONTINUATION
script_continue() {
	while true; do
		read -r -p "? " yn
		case $yn in
		[Yy]*) return 0 ;; # RETURN 0 if YES IS CHOSEN
		[Nn]*) return 1 ;; # RETURN 1 if NO IS CHOSEN
		*) ;;              # DO NOTHING ON OTHER INPUTS
		esac
	done
}

# INTERCEPT UPCOMING ERRORS (EXIT'S WITH NON-ZERO) & INTERRUPTIONS (CTRL-C)
trap "script_error" ERR
trap "script_ctrlc" INT

###
# FUNCTION BLOCKS TO CHECK AVAILABILITY OF SOFTWARE NEEDED FOR INSTALL
###
probe_unix_tool_mandatory() {
if ! command -v $UNIX_TOOL_NAME >/dev/null 2>&1; then
	echo ""; echo "FAIL ⛔️ MANDATORY, INSTALL $UNIX_TOOL_NAME";
	echo "USE: $UNIX_TOOL_INSTALL";
	echo "";
    script_error;
else
	echo "  OK ✅ $UNIX_TOOL_NAME";
fi
}

probe_unix_tool_optional() {
if ! command -v "${UNIX_TOOL_NAME}" >/dev/null 2>&1; then
	echo ""; echo "SKIP 🅿️ OPTIONAL, INSTALL $UNIX_TOOL_NAME";
	echo "USE: $UNIX_TOOL_INSTALL";
	echo "";
else
	echo "  OK ✅ $UNIX_TOOL_NAME";
fi
}

probe_npm() {
export UNIX_TOOL_NAME="npm";
export UNIX_TOOL_INSTALL="brew install npm";
probe_unix_tool_mandatory
}

probe_node() {
export UNIX_TOOL_NAME="node";
export UNIX_TOOL_INSTALL="brew install node@22";
probe_unix_tool_mandatory
}

probe_python() {
export UNIX_TOOL_NAME="python3";
export UNIX_TOOL_INSTALL="brew install python@3.11";
probe_unix_tool_mandatory
}

probe_docker() {
export UNIX_TOOL_NAME="docker";
export UNIX_TOOL_INSTALL="brew install --cask docker";
probe_unix_tool_mandatory
}

probe_docker_daemon() {
if ! docker stats --no-stream >/dev/null 2>&1; then
    open -a Docker
    max_tries=10
    current_try=1
    while ! docker stats --no-stream >/dev/null 2>&1; do
        echo ""
        echo "FAIL ⛔️ docker daemon — NOT YET RUNNING, WAITING FOR LAUNCH ..."
        sleep 2
        ((current_try++))
        if (( current_try > max_tries )); then
            log error "COULD NOT LAUNCH DOCKER DAEMON"
            script_error
        fi
    done
    echo "  OK ✅ docker daemon — SUCCESSFULLY LAUNCHED"
else
    echo "  OK ✅ docker daemon"
fi
}

probe_black() {
export UNIX_TOOL_NAME="black";
export UNIX_TOOL_INSTALL="brew install black";
probe_unix_tool_optional
}

probe_shellcheck() {
export UNIX_TOOL_NAME="shellcheck";
export UNIX_TOOL_INSTALL="brew install shellcheck";
probe_unix_tool_optional
}

probe_flake8() {
export UNIX_TOOL_NAME="flake8";
export UNIX_TOOL_INSTALL="python -m pip install flake8";
probe_unix_tool_optional
}

probe_clock() {
export UNIX_TOOL_NAME="cloc";
export UNIX_TOOL_INSTALL="brew install cloc";
probe_unix_tool_optional
}

# WARNING: THIS IS A VERY DANGEROUS OPERATION RESETTING DOCKER THE HARD WAY
exec_force_full_reset_docker() {
	log cyan "DOCKER: PRUNING ALL VOLUMES"
	log
	docker system prune --volumes
	log
	log green "DOCKER: SUCCESSFULLY PRUNED VOLUMES"
    log cyan "DOCKER: PRUNING ALL IMAGES"
    log
    docker image prune -a
    log
    log green "DOCKER: SUCCESSFULLY PRUNED IMAGES"
    log cyan   "LARAVEL: ERASING 'vendor' FOLDER"
    if [[ -d "vendor" ]]; then
		log
		log warn "REMOVING FOLDER 'vendor' ..."
        rm -rf ./vendor
        log green "FOLDER REMOVED"
	else
        log
		log green "FOLDER NOT EXISTING"
	fi
    log
    log cyan   "DOCKER: FORCE RELAUNCHING DOCKER DAEMON ..."
    log cyan   "DOCKER: QUITTING ..."
    killall "Docker Desktop"
    log cyan   "DOCKER: WAITING 10 SECONDS ..."
    sleep 10
    log cyan   "DOCKER: LAUNCHING ..."
    open -a Docker
    log
    log green "DOCKER: RELAUNCHED"
}

###
# MAIN SCRIPT STARTING HERE
###

exec_clear_screen() {
	# IF NOT IN AUTO-INSTALL MODE CLEAR SCREEN (INTERACTION WITH USER)
	if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
		clear
	else
		log
		log
		log	
	fi
}

exec_logo_launch() {
	# LOGO see # LOGO see https://textkool.com/en/ascii-art-generator?hl=controlled%20smushing&vl=default&font=Big&text=opnqda
	exec_clear_screen
	log "                                   _       "
	log "                                  | |      "
	log "   ___  _ __   ___ _ __   __ _  __| | __ _ "
	log "  / _ \\| '_ \\ / _ \\ '_ \ / _\` |/ _\` |/ _\` |"
	log " | (_) | |_) |  __/ | | | (_| | (_| | (_| |"
	log "  \\___/| .__/ \\___|_| |_|\\__, |\\__,_|\\__,_|"
	log "       | |                  | |            "
	log "       |_|                  |_| v${SCRIPT_VERSION} build ${SCRIPT_BUILD}"
	log ""
	log yellow "HOME: ${SCRIPT_HOME}"
	log yellow "SELF: ${SCRIPT_SELF}"
	log info "*** DOCKER INSTALLATION ***"
}

exec_welcome_short() {
	# START OF NORMAL OPERATIONS
	log info "*** WELCOME TO ${SCRIPT_NAME} - VERSION ${SCRIPT_VERSION} BUILD ${SCRIPT_BUILD} ***"
	log
	log white "Following script will help with installation and running"
	log white "of the ${SCRIPT_PRODUCT_NAME} software using DOCKER."
	log
	log red-b "IMPORTANT!!!"
	log white "If this is your first run of this script, please do"
	log white "the following in the following order:"
	log
	log cyan "1. Check Tooling"
	log cyan "2. Configure Environment"
	log cyan "3. Install & First Run"
	log cyan "4. Start Frontend"
	log
	log red-b "OR"
	log
	log white "Quit this script now and restart the script by calling"
	log cyan "${SCRIPT_SELF} --auto-install"
	log
	log white "If one of these were successful you can call this script"
	log white "whenever you need functions to conveniently execute single"
	log white "operations on your ${SCRIPT_PRODUCT_NAME} installation."
	log
}


exec_welcome_screen() {
	exec_welcome_short
	# ONLY STOP WITH INTERRUPT WHEN NOT IN AUTOINSTALL MODE
	if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
		log hint "${SCRIPT_PRESS_ANY_KEY}"
		read -r -n 1
	fi

	# CHECK IF WE RUN FROM WITHIN WEB FOLDER
	if [[ ${PWD##*/} != "${SCRIPT_WEB_DIR}" ]]; then
	log warn "ERROR"
	log red "THIS SCRIPT NEEDS TO RUN FROM WITHIN THE 'web' DIRECTORY."
	script_abort
	else
	log green "RUNNING INSIDE OF WEB DIRECTORY"
	log ok "OK"
	fi
}

exec_usage() {
  cat <<EOF
$SCRIPT_NAME $SCRIPT_VERSION

Usage:
  $SCRIPT_BASE_NAME [options]

Options:
  -h, --help       Show this help screen
  --no-color       Disable ANSI colors
  --auto-install   Automatically install everything
  --use-defaults   Use default values on auto-install mode

Examples:
  $SCRIPT_BASE_NAME
  $SCRIPT_BASE_NAME --no-color
  $SCRIPT_BASE_NAME --auto-install --use-defaults

EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-color)
      SCRIPT_USE_COLOR=0
      shift
      ;;
    --auto-install)
	  SCRIPT_EXECUTE_AUTOINSTALL=1
      shift
      ;;
    --use-defaults)
	  SCRIPT_USE_DEFAULTS=1
      shift
      ;;
    -h|--help)
	  SCRIPT_USE_COLOR=0
      exec_usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
	  SCRIPT_USE_COLOR=0
      exec_usage
      exit 1
      ;;
  esac
done

# ACTIVATE AUTOKILL OF PROCESSES STARTED WITHIN CONTEXT OF THIS SCRIPT
trap "script_kill" EXIT
exec_logo_launch

# DISALLOW THIS TO BE RUN AS ROOT USER
if [[ $UID -eq 0 ]]; then
	log warn-b "THIS SCRIPT SHOULD NEVER BE RUN WITH ROOT USER (IT WILL REQUEST MORE RIGHTS IF NEEDED) ..."
	echo -n "ENTER DIFFERENT MACHINE USER (DEFAULT '${SCRIPT_DEFAULT_USER}'): "
	read -r SCRIPT_USER
	if [ -z "${SCRIPT_USER}" ]; then
		SCRIPT_USER=$SCRIPT_DEFAULT_USER
		log green "TRYING TO RE-EXECUTE WITH USER: ${SCRIPT_USER} ..."
	else
		log green "TRYING TO RE-EXECUTE WITH USER: ${SCRIPT_USER} ..."
	fi
	sudo -u "${SCRIPT_USER} ${SCRIPT_SELF}" || log warn-b "FAILED TO EXECUTE WITH USER '${SCRIPT_USER}' ..."
fi

if [[ $UID -eq 0 ]]; then
	log warn "ROOT USER STILL ACTIVE (ABORTING) ..."
	script_abort
fi


exec_welcome_screen

##
# FUNCTIONS CALLED IN SCRIPT
##

exec_check_tooling() {
	exec_logo_launch
	exec_welcome_short
	# CHECK IF NEEDED HELPER TOOLS ARE INSTALLED & AVAILABLE
	log info "INSTALLER: CHECKING INSTALLED HELPER TOOLS"
	probe_docker;
	probe_docker_daemon;
	probe_npm;
	probe_node;
    probe_python;
	probe_shellcheck;
	#probe_clock;
	#probe_black;
	#probe_flake8;
	log
	log green "MANDATORY TOOLS ARE INSTALLED & AVAILABLE"
	log ok "OK"
}

exec_start_backend() {
	exec_logo_launch
	exec_welcome_short
    # LAUNCHING BACKEND
    log info "INSTALLER: STARTING BACKEND"
    log
	if [[ -d "vendor" ]]; then
    #./vendor/bin/sail up
    ./vendor/bin/sail up -d
	log
	log green "BACKEND STARTED"
	log ok "OK"
	else
	log
	log red "FAILED TO START, NO vendor DIRECTORY FOUND."
	log warn "FAILED"
	fi
}

exec_stop_backend() {
	exec_logo_launch
	exec_welcome_short
    # TERMINATING BACKEND
    log info "INSTALLER: STOPPING BACKEND"
    log
	if [[ -d "vendor" ]]; then
    #./vendor/bin/sail up
    ./vendor/bin/sail down
	log
	log green "BACKEND STOPPED"
	log ok "OK"
	else
	log
	log red "FAILED TO STOP. NO vendor DIRECTORY FOUND."
	log warn "FAILED"
	fi
}

exec_is_backend_up() {
	# CHECK IF VENDOR DIR EXISTS
	if [[ -d "vendor" ]]; then
		# CHECK IF DOCKER CONTAINERS ARE UP
		if [[ $(./vendor/bin/sail ps | grep -q "Up") -eq 0 ]]; then
			log yellow "BACKEND IS UP"
			# IS UP
			return 0
		else
			log yellow "BACKEND IS DOWN"
			# IS DOWN
			return 1
		fi
	else
		log yellow "VENDOR DIR NOT FOUND"
		# vendor DOES NOT EXIST / NOTHING RUNNING
		return 1
	fi
}

exec_start_frontend() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: STARTING FRONTEND"
	log
	log cyan "CHECKING IF BACKEND IS UP AND RUNNING..."
	# CHECK IF VENDOR DIR EXISTS
	if [[ -d "vendor" ]]; then
		# CHECK IF CONTAINER IS RUNNING
		if docker ps --filter "name=web-laravel.test-1" | grep -q "web-laravel.test-1"; then
			log cyan "RUNNING INSTALLING NPM PACKAGES ..."
			./vendor/bin/sail npm install
			# ONLY ASK IF NOT RUN IN AUTO-INSTALL MODE
			if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
				log hint "${SCRIPT_PRESS_ANY_KEY}"
				read -r -n 1
			fi
			log cyan "RUNNING DEVELOPMENT FRONTEND (STOP WITH CTRL-D) ..."
			./vendor/bin/sail npm run dev
			# STORE PROCESS ID
			# SCRIPT_PID_FRONTEND=$!
			log
			log green "INSTALLER: FRONTEND STARTED"
			log green "INSTALLER: FRONTEND STOPPED"
			# log yellow "TO STOP FRONTEND PLEASE ENTER:"
			# log yellow "kill ${SCRIPT_PID_FRONTEND}"
			log ok "OK"
		else
			log "NO BACKEND RUNNING. PLEASE START THE BACKEND FIRST THEN TRY TO START FRONTEND."	
			log red "BACKEND IS NOT AVAILABLE. CANCELLING START OF FRONTEND."
			log warn "ERROR"
		fi
	else
		log
		log red "NO vendor DIRECTORY FOUND FOR BACKEND."
		log warn "FAILED"
	fi
}

exec_stop_frontend() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: STOPPING FRONTEND"
	log
	log cyan "CHECKING IF FRONTEND IS RUNNING..."
	if [[ SCRIPT_PID_FRONTEND -eq 0 ]]; then
		log red "FRONTEND IS NOT RUNNING. NOTHING TO STOP."
		log warn "FAILED"
	else
		log cyan "STOPPING FRONTEND NOW..."
		kill $SCRIPT_PID_FRONTEND
		SCRIPT_PID_FRONTEND=0
		log green "FRONTEND STOPPED."
		log ok "OK"
	fi
}

# TODO: ASK FOR DEFAULT VALUES INTERACTIVELY
exec_clone_env_and_enter_values() {
	SCRIPT_DB_PASSWORD_MYSQL_VALUE=$SCRIPT_DB_PASSWORD_MYSQL_DEFAULT
	SCRIPT_REVERB_HOST_VALUE=$SCRIPT_REVERB_HOST_DEFAULT
	SCRIPT_REVERB_PORT_VALUE=$SCRIPT_REVERB_PORT_DEFAULT
	log
	log cyan "CLONING FROM TEMPLATE ENV FILE ..."
	if [[ -f "${SCRIPT_ENV_TEMPLATE}" ]]; then
		cp ${SCRIPT_ENV_TEMPLATE} ${SCRIPT_ENV}
		log ok "OK"
		# ASK USER FOR DEFAULTS ... (ONLY IF SCRIPT_USE_DEFAULTS==0)
		if [[ $SCRIPT_USE_DEFAULTS -eq 0 ]]; then
			log ok "ASKING FOR SECRET ENV VALUES"
			# MYSQL PASSWD
			echo -n "ENTER PASSWORD FOR MYSQL DB (DEFAULT '${SCRIPT_DB_PASSWORD_MYSQL_DEFAULT}'): "
			read -r SCRIPT_DB_PASSWORD_MYSQL_VALUE
			if [ -z "${SCRIPT_DB_PASSWORD_MYSQL_VALUE}" ]; then
				log green "USING DEFAULT VALUE: ${SCRIPT_DB_PASSWORD_MYSQL_DEFAULT}"
				SCRIPT_DB_PASSWORD_MYSQL_VALUE=$SCRIPT_DB_PASSWORD_MYSQL_DEFAULT
			else
				log green "USING ENTERED VALUE: ${SCRIPT_DB_PASSWORD_MYSQL_VALUE}"
			fi
			# REVERB HOST
			log 
			echo -n "ENTER HOST FOR REVERB SERVICE (DEFAULT '${SCRIPT_REVERB_HOST_DEFAULT}'): "
			read -r SCRIPT_REVERB_HOST_VALUE
			if [ -z "${SCRIPT_REVERB_HOST_VALUE}" ]; then
				log green "USING DEFAULT VALUE: ${SCRIPT_REVERB_HOST_DEFAULT}"
				SCRIPT_REVERB_HOST_VALUE=$SCRIPT_REVERB_HOST_DEFAULT
			else
				log green "USING ENTERED VALUE: ${SCRIPT_REVERB_HOST_VALUE}"
			fi
			# REVERB PORT
			log 
			echo -n "ENTER PORT FOR REVERB SERVICE (DEFAULT '${SCRIPT_REVERB_PORT_DEFAULT}'): "
			read -r SCRIPT_REVERB_PORT_VALUE
			if [ -z "${SCRIPT_REVERB_PORT_VALUE}" ]; then
				log green "USING DEFAULT VALUE: ${SCRIPT_REVERB_PORT_DEFAULT}"
				SCRIPT_REVERB_PORT_VALUE=$SCRIPT_REVERB_PORT_DEFAULT
			else
				log green "USING ENTERED VALUE: ${SCRIPT_REVERB_PORT_VALUE}"
			fi
		else
			log warn "SKIPPING USER INPUT. USING HARDCODED DEFAULT VALUES."
			log green "USING MYSQL PASSWORD: ${SCRIPT_DB_PASSWORD_MYSQL_DEFAULT}"
			log green "USING REVERB HOST: ${SCRIPT_REVERB_HOST_DEFAULT}"
			log green "USING REVERB PORT: ${SCRIPT_REVERB_PORT_DEFAULT}"
			log
		fi

		# do cat & stream replacements for mysql passwords
		log cyan "CONFIGURATION: INJECTING MYSQL PASSWORD ..."
		CONTENT_ORIGINAL=$(cat "${SCRIPT_ENV}")
		CONTENT_SEARCH_STR=$SCRIPT_DB_PASSWORD_MYSQL
		
		CONTENT_REPLACE_STR=$SCRIPT_DB_PASSWORD_MYSQL_VALUE
		CONTENT_MODIFIED="${CONTENT_ORIGINAL//$CONTENT_SEARCH_STR/$CONTENT_REPLACE_STR}"
		log cyan "CONFIGURATION: GENERATING ALTCHA KEY ..."
		KEY_GENERATED=$(python3 -c 'import hashlib;import base64;import hmac;print(hmac.new(b"nonbase64key", "password".encode(), hashlib.sha256).hexdigest())')
		log cyan "CONFIGURATION: INJECTING ALTCHA KEY ..."
		CONTENT_SEARCH_STR=$SCRIPT_ALTCHA_HMAC_KEY
		CONTENT_REPLACE_STR=$KEY_GENERATED
		CONTENT_MODIFIED="${CONTENT_MODIFIED//$CONTENT_SEARCH_STR/$CONTENT_REPLACE_STR}"
		log cyan "CONFIGURATION: INJECTING REVERB HOST ..."
		CONTENT_SEARCH_STR=$SCRIPT_REVERB_HOST
		
		CONTENT_REPLACE_STR=$SCRIPT_REVERB_HOST_VALUE
		CONTENT_MODIFIED="${CONTENT_MODIFIED//$CONTENT_SEARCH_STR/$CONTENT_REPLACE_STR}"
		log cyan "CONFIGURATION: INJECTING REVERB PORT ..."
		CONTENT_SEARCH_STR=$SCRIPT_REVERB_PORT
		
		CONTENT_REPLACE_STR=$SCRIPT_REVERB_PORT_VALUE
		CONTENT_MODIFIED="${CONTENT_MODIFIED//$CONTENT_SEARCH_STR/$CONTENT_REPLACE_STR}"
		log cyan "CONFIGURATION: WRITING ENV ..."
		# FINALLY WRITE ADJUSTED FILE
		echo "${CONTENT_MODIFIED}" > "${SCRIPT_ENV}"
		log ok "OK"
	else
		log warn "TEMPLATE ENV FILE ${SCRIPT_ENV_TEMPLATE} NOT FOUND. ABORTING."
	fi
}

# WILL PICK CORRECT DOCKER COMPOSE TEMPLATE FOR THIS SCRIPT
exec_prepare_docker_compose() {
	log info "INSTALLER: PREPARING DOCKER COMPOSE FILE"
	log
	# CHECK IF TEMPLATE FOR MAC EXISTS
	log cyan "CHECKING IF ${SCRIPT_DOCKER_TEMPLATE} TEMPLATE EXISTS.."
	if [[ -f "${SCRIPT_DOCKER_TEMPLATE}" ]]; then
		log green "FOUND ${SCRIPT_DOCKER_TEMPLATE} TEMPLATE."
		log cyan "CHECKING IF TARGET ${SCRIPT_DOCKER_TARGET} ALREADY EXISTS."
		# CHECK IF THE FILE ALREADY EXISTS
		if [[ -f "${SCRIPT_DOCKER_TARGET}" ]]; then
			log yellow "${SCRIPT_DOCKER_TARGET} ALREADY EXISTS."
			# CHECK IF WE ALREADY HAVE A BACKUP
			if [[ -f "${SCRIPT_DOCKER_BACKUP}" ]]; then
				# DO NOTHING WE DO NOT WANT TO OVERWRITE THE BACKUP
				log yellow "WE HAVE ALREADY A VALID BACKUP OF ${SCRIPT_DOCKER_TARGET}. NO BACKUP NEEDED."
			else
				log red "NO BACKUP FOUND YET. WE NEED A BACKUP."
				log cyan "CREATING A BACKUP FROM ${SCRIPT_DOCKER_TARGET} AS ${SCRIPT_DOCKER_BACKUP} ..."
				cp $SCRIPT_DOCKER_TARGET $SCRIPT_DOCKER_BACKUP
				log ok "OK"
			fi
		else
			log green "NO BACKUP NEEDED BECAUSE NO ${SCRIPT_DOCKER_TARGET} EXISTING."
		fi
		# NOW CLONE FROM TEMPLATE
		log cyan "CLONING FROM TEMPLATE ${SCRIPT_DOCKER_TEMPLATE} TO ${SCRIPT_DOCKER_TARGET} ..."
		cp $SCRIPT_DOCKER_TEMPLATE $SCRIPT_DOCKER_TARGET
		log green "DOCKER COMPOSE CONFIGURATION SUCCEEDED."
		log ok "OK"
	else
		log red "NO TEMPLATE ${SCRIPT_DOCKER_TEMPLATE} FOUND. NOTHING TO DO."
		log warn "ERROR"
	fi
}

exec_configure_env() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: CONFIGURING ENVIRONMENT"
	log
	# BYPASS QUESTIONS IN AUTO-INSTALL MODE
	if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 1 ]]; then
		exec_clone_env_and_enter_values
		exec_prepare_docker_compose
		return
	fi

	# check if .env is even there
	if [[ -f "${SCRIPT_ENV}" ]]; then
		log green "AN .env FILE WAS FOUND."
		# if YES ask to reset it (yes/no)
		log attn " WANT TO RESET .env TO FACTORY DEFAULTS? "
		while true; do
			SCRIPT_ASK=$(log yellow " Yes / No [y/N]: ")
			read -r -p "${SCRIPT_ASK}" INPUT
			case $INPUT in
			[Yy]*)
				exec_clone_env_and_enter_values
				exec_prepare_docker_compose
				break
				;;
			[Nn]*)
				export SCRIPT_SHOULD_CLEAN=0
				log
				log green "KEEPING .env UNTOUCHED"
				break
				;;
			*)
				export SCRIPT_SHOULD_CLEAN=0
				log
				log green "KEEPING .env UNTOUCHED"
				break
				;;
			esac
		done

	else
		log red "NO .env FILE FOUND."
		# if NO clone from template
		# and ask for default values
		exec_clone_env_and_enter_values
	fi
	# if YES then remove & clone from template
	# if NO just exit this function
	log ok "OK"
}

exec_clean_containers() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: CLEAR/DELETE INSTALLED CONTAINERS"
	log
	log white "This operation will stop the running ${SCRIPT_PRODUCT_NAME}"
	log white "in docker and clean the laravel-vendor folder. We recommend"
    log white "this operation only if you encounter any severe issues."
	log
	# ONLY ASK IF NOT RUN IN AUTO-INSTALL MODE
	if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
		log hint "${SCRIPT_PRESS_ANY_KEY}"
		read -r -n 1
	fi	
	log
	log cyan-b "CLEANING... (DO NOT INTERRUPT!!!)"
    if [[ -d "vendor" ]]; then
        log cyan   "DOCKER: STOPPING RUNNING CONTAINERS"
        log
        ./vendor/bin/sail down
        log
        log green "DOCKER: CONTAINERS STOPPED"
        log
		log cyan "DOCKER: REMOVING VENDOR DIRECTORY..."
		rm -rf "vendor"
		log
        log green "DOCKER: DIRECTORY REMOVED"
    fi
    log cyan   "DOCKER: WINDING DOWN ALSO VOLUMES"
    log
    docker compose down -v    
    log
	log green "DOCKER: CONTAINERS CLEARED / DELETED"
	log ok "OK"
}

exec_install_and_firstrun() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: INSTALL CONTAINERS & FIRST INIT RUN"
	log
	log white "This will create & run a LARAVEL container image,"
	log white "execute a composer install inside of the container"
	log white "and on shutdown, remove the container."
	# ONLY ASK IF NOT RUN IN AUTO-INSTALL MODE
	if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
	    log hint "${SCRIPT_PRESS_ANY_KEY}"
	    read -r -n 1
	fi
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
    log
    log green "LARAVEL INSTALLED & CONTAINER IMAGE CREATED"
    log ok "OK"

    # BUILDING PRODUCT CONTAINER
    log info "INSTALLER: BUILDING CONTAINER IMAGES USING SAIL"
    log
    ./vendor/bin/sail build --no-cache
    log
    log green "CONTAINER IMAGES BUILT SUCCESSFULLY"
    log ok "OK"

    # LAUNCHING BACKEND
    log info "INSTALLER: LAUNCHING BACKEND"
    log
    #./vendor/bin/sail up
    ./vendor/bin/sail up -d
    log
    log green "INSTALLER: BACKEND LAUNCHED"
    log ok "OK"

    # GENERATING APPLICTAION KEYS (IF FIRST RUN)
    log info "INSTALLER: GENERATING APPLICATION KEYS"
    log
    ./vendor/bin/sail artisan key:generate
    log
    log green "APPLICATION KEYS SET"
    log ok "OK"

    # CONFIGURE DATABASE REALTED STUFF (ON FIRST RUN)
    log info "INSTALLER: CONFIGURE CACHES & DATABASE"
    log
    ./vendor/bin/sail artisan config:cache
	sleep 3
    ./vendor/bin/sail artisan config:clear
	sleep 3
    ./vendor/bin/sail artisan migrate
    log
    log green "CACHES & DATABASE CONFIGURED"
    log ok "OK"

    # SEEDING DB (ON FIRST RUN)
    log info "INSTALLER: SEEDING DATABASE"
    log
    ./vendor/bin/sail artisan db:seed
    log
    log green "DATABASE SEEDED"
    log ok "OK"

    # MAKE IMAGES LOCALLY AVAILABLE (ON FIRST RUN)
    log info "INSTALLER: MAKE IMAGES LOCALLY AVAILABLE"
    log
    ./vendor/bin/sail artisan storage:link
    log
    log green "IMAGES MADE LOCALLY AVAILABLE"
    log ok "OK"

	log green "INSTALLATION OF CONTAINERS & FIRST INIT RUN SUCCESSFULLY COMPLETED"
	log ok "OK"
}

exec_test_backend() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: TESTING SYSTEM SETUP"
	log red "<NOT YET IMPLEMENTED>"
	log warn "ERROR"
}

exec_test_pre_commit() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: TESTING CODE QUALITY / COMPLIANCE"
	log cyan "RUNNING PINT (PHP-CS-Fixer) ..."
	./vendor/bin/pint -vv --test
	log ok "OK"
	log cyan "RUNNING NPM LINTER ..."
	npm run lint:check
	log ok "OK"
	log cyan "RUNNING NPM FORMATTER ..."
	npm run format:check
	log ok "OK"
}

exec_zap_docker() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: ZAPPING & HARD RESETTING DOCKER ENGINE"
	# ASK
	log warn " WANT TO REALLY ZAP & HARD-RESET (DANGER!!) DOCKER ENGINE? "
	while true; do
		SCRIPT_ASK=$(log red " Yes / No [y/N]: ")
		read -r -p "${SCRIPT_ASK}" INPUT
		case $INPUT in
		[Yy]*)
		    log
			log red-b "LAST WARNING: YOU ARE ABOUT TO ZAP & HARD RESET DOCKER ENGINE !!!"
			log hint "${SCRIPT_PRESS_ANY_KEY}"
			read -r -n 1
			exec_stop_backend
			exec_clean_containers
			exec_force_full_reset_docker
			log green "DOCKER ZAPPED"
			log ok "OK"
			break
			;;
		[Nn]*)
			log
			log ok "CANCELLED"
			break
			;;
		*)
			log
			log ok "CANCELLED"
			break
			;;
		esac
	done
}

exec_shell_check() {
	exec_logo_launch
	exec_welcome_short
	log info "INSTALLER: CHECKING SHELL SCRIPTS IN PROJECT"
	log
	log white "Following operation checks if all shell-Scripts in the"
	log white "project do conform to best practice and do not contain"
	log white "major issues."
	log
	log hint "${SCRIPT_PRESS_ANY_KEY}"
	read -r -n 1
	log cyan "SEARCHING FILES IN PROJECT ROOT DIR ..."
	find .. -type f -name '*.sh'
	log
	log cyan "RUNNING SHELLCHECK ON FOUND FILES ..."
	find .. -type f -name '*.sh' -print0 | xargs -0 shellcheck
	log ok "OK"
}

exec_auto_installation() {
	exec_logo_launch
    log info "AUTO-INSTALLER: STARTING FULL INSTALLATION"
	log white "We will now automatically execute a bunch of operations"
	log white "to install a fully working ${SCRIPT_PRODUCT_NAME} installation."
	log red-b "ATTENTION!"
	log white "We will do a tool check first, if the needed tools are not there"
	log white "you need to install those tools and make them available first."
	log white "Once the missing tools are there, retry the script with"
	log yellow "${SCRIPT_BASE_NAME} --auto-install"
	log
	log white "We will setup several docker comtainer images and initialize"
	log white "a MySQL database and populate it with some initial data."
	log
	log white "If everything runs with lots of OK messages, you will have a"
	log white "launched and running ${SCRIPT_PRODUCT_NAME} backend."
	log white "We will also launch the frontend for you so that you only need"
	log white "point the browser to http://localhost to start working on your"
	log white "instance."
	# EXECUTE NECESSARY STEPS ONE AFTER THE OTHER IN AUTO-INSTALL MODE
	exec_check_tooling
	# CLEANUP BEFORE REINSTALL
	exec_stop_backend
	exec_clean_containers
	exec_configure_env
	exec_install_and_firstrun
	log green "AUTO-INSTALLATION COMPLETED"
	log ok "OK"	
	exec_start_frontend
}

exec_print_menu() {
  log attn "  FUNCTION MENU  "
  log white " 1) Check Tooling"
  log white " 2) Configure Environment"
  log white " 3) Install & First Run"
  log white " 4) Start Frontend"
  log white " 5) Start Backend"
  log white " 6) Stop Backend"
  log white " 7) Clean Containers"
  log white " 8) Pre-Commit Code Testing"
  log white " 9) Shellcheck Scripts"
  log white "10) Docker ZAPPING!!!"
  log white " 0) EXIT/QUIT"
  log attn "(CHOOSE AN OPTION)"  
}

##
# FUNCTION MENU (MAIN)
##
# ONLY ENTER MENU WHEN NOT IN AUTOINSTALL MODE
if [[ SCRIPT_EXECUTE_AUTOINSTALL -eq 0 ]]; then
	while true; do
	exec_print_menu
	read -r choice_raw
	if [[ -z "$choice_raw" ]]; then
		exec_logo_launch
		exec_welcome_short
		continue
	fi

	case "$choice_raw" in
		1) exec_check_tooling ;;
		2) exec_configure_env ;;
		3) exec_install_and_firstrun ;;
		4) exec_start_frontend ;;
		5) exec_start_backend ;;
		6) exec_stop_backend ;;
		7) exec_clean_containers ;;
		8) exec_test_pre_commit ;;
		9) exec_shell_check ;;
		10) exec_zap_docker ;;
		0) script_exit ;;
		q) script_exit ;;
		x) script_exit ;;
		*)
		log warn "INVALID CHOICE: '$choice_raw'"
		;;
	esac
	done
else
	exec_logo_launch
	exec_auto_installation
	script_exit
fi

## LEGACY STUFF
if [[ $SCRIPT_SHOULD_COLLAB -gt 0 ]]; then
    log green "WE LAUNCH COLLABORATION SERVICES."
    log cyan "STARTING CONTAINERS IF NOT YET RUNNING"
    ./vendor/bin/sail up -d
    log cyan "STARTING DEBUG SERVICES FOR COLLABORATION"
    docker exec -it web-laravel.test-1 /var/www/html/start_debug_services.sh &
    log green "STARTED SERVICES"
    log OK
else
    log red "WE DO *NOT* LAUNCH COLLABORATION SERVICES."
fi


# ALL DONE
log info "*** ${SCRIPT_NAME} INSTALL COMPLETE - VERSION ${SCRIPT_VERSION} BUILD ${SCRIPT_BUILD} ***"
log info "*** GOOD BYE! ***"
log
