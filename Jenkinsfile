pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        LOG_FOLDER = 'pipeline-logs'
        CONTAINER_FILES_PATH = '/var/lib/jenkins/workspace/container-files' // Full path to container files
    }

    stages {
        stage('Prepare Environment') {
            steps {
                script {
                    sh "mkdir -p ${CONTAINER_FILES_PATH}"
                    if (fileExists("${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run")) {
                        echo "XAMPP installer already exists at ${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run."
                    } else {
                        sh "wget https://sourceforge.net/projects/xampp/files/latest/download -O ${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run"
                    }
                }
            }
        }

        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main', url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('Build and Test Containers') {
            parallel {
                stage('Build Apache Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "Apache image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL image built successfully."
                        }
                    }
                }
            }
        }
         stage('Run SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'SonarScanner'; 
                    withSonarQubeEnv('SonarQube') { 
                        sh "${scannerHome}/bin/sonar-scanner"
                    }
                    echo "SonarQube scan completed."
                }
            }
        }

        stage('Gatekeeper Approval') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to deploy or rollback?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['good', 'bad'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

          stage('Deploy Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'good' }
            }
            steps {
                script {
                    echo "Cleaning up any conflicting networks..."
                    sh """
                    docker network ls | grep -q container-files_container_network && docker network rm container-files_container_network || echo 'No conflicting network to remove'
                    docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml down
                    docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml up -d
                    """
                }
            }
        }


        stage('Rollback') {
            when {
                expression { env.DEPLOY_STATUS == 'bad' }
            }
            steps {
                script {
                    echo "Rollback initiated."
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs for details.'
        }
    }
}
