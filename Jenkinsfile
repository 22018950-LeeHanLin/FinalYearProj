sudo apt update && sudo apt upgrade -y
sudo apt install openjdk-11-jdk -y
java -version

sudo wget -O /usr/share/keyrings/jenkins-keyring.asc \
    https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key
  
  echo "deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc]" \
    https://pkg.jenkins.io/debian-stable binary/ | sudo tee \
    /etc/apt/sources.list.d/jenkins.list > /dev/null

  sudo apt-get update
  sudo apt-get install fontconfig openjdk-17-jre
  sudo apt-get install Jenkins


sudo systemctl start jenkins.service


Jenkins: 
Username: admin
pw: Jenkins@2024
full name: adminfyp
http://localhost:8080/jenkinsFYP



pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'FYPtesting'
        DOCKER_IMAGE = 'fyp-app:1.0'
        DOCKER_CONTAINER = 'fyp-app-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        GIT_CREDENTIALS = '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'
        GIT_USERNAME = 'githubadmin'
        LOG_FOLDER = 'pipeline-logs' 
}

    triggers {
        pollSCM('* * * * *') // Polling every minute
    }


    stages {
        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main',
                        credentialsId: '0ca8f70f-0dae-44d3-b15d-2d8ad577e89c', // Jenkins credentials ID
                        url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('Run Parallel Tests') {
            parallel {
                stage('Run SonarQube Analysis') {
                    steps {
                        script {
                            withSonarQubeEnv('SonarQube') {
                                def scannerHome = tool name: 'SonarScanner', type: 'hudson.plugins.sonar.SonarRunnerInstallation'
                                sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${SONAR_PROJECT_KEY} -Dsonar.host.url=${SONAR_HOST}"
                            }
                        }
                    }
                }

                stage('Dummy API Test') {
                    steps {
                        echo "Running dummy API test..."
                        sh "curl -X GET http://localhost:8080/health || true"
                        echo "Dummy API test completed."
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                sh "docker build -t ${DOCKER_IMAGE} ."
                echo "Docker image built: ${DOCKER_IMAGE}"
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

        stage('Deploy or Rollback') {
            steps {
                script {
                    if (env.DEPLOY_STATUS == 'good') {
                        echo "Deployment approved. Proceeding..."
                        sh """
                        docker stop ${DOCKER_CONTAINER} || true
                        docker rm ${DOCKER_CONTAINER} || true
                        docker run -d --name ${DOCKER_CONTAINER} -p 8080:80 ${DOCKER_IMAGE}
                        """
                        echo "Container deployed: ${DOCKER_CONTAINER}"
                    } else {
                        echo "Rollback initiated."
                        sh './rollback.sh'
                    }
                }
            }
        }

        stage('Clean Old Containers') {
            steps {
                sh './cleanup-containers.sh'
                echo "Old containers and networks cleaned."
            }
        }

        stage('Log Results to GitHub') {
            steps {
                script {
                    sh """
                    mkdir -p ${LOG_FOLDER}
                    echo 'Pipeline execution log' > ${LOG_FOLDER}/log.txt
                    git config --global user.email "you@example.com"
                    git config --global user.name "Your Name"
                    git add ${LOG_FOLDER}
                    git commit -m 'Pipeline logs updated'
                    git push https://${GIT_USERNAME}:${GIT_CREDENTIALS}@${GIT_REPO}
                    """
                    echo "Logs uploaded to GitHub."
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs in GitHub.'
        }
    }
}






pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'FYPtesting'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        GIT_CREDENTIALS = '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'
        GIT_USERNAME = 'githubadmin'
        LOG_FOLDER = 'pipeline-logs'
        CONTAINER_FILES_PATH = '~/fyp/Jingyi/container-files' // Path to container files
    }

    triggers {
        pollSCM('* * * * *') // Polling every minute
    }

    stages {
        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main',
                        credentialsId: '570f5130-98ad-4f82-ac6b-4ec94f041e3e',
                        url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('Run Parallel Tests') {
            parallel {
                stage('Run SonarQube Analysis') {
                    steps {
                        script {
                            withSonarQubeEnv('SonarQube') {
                                def scannerHome = tool name: 'SonarScanner', type: 'hudson.plugins.sonar.SonarRunnerInstallation'
                                sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${SONAR_PROJECT_KEY} -Dsonar.host.url=${SONAR_HOST}"
                            }
                        }
                    }
                }

                stage('Dummy API Test') {
                    steps {
                        echo "Running dummy API test..."
                        sh "curl -X GET http://localhost:8080/health || true"
                        echo "Dummy API test completed."
                    }
                }
            }
        }

        stage('Build Docker Images') {
            parallel {
                stage('Build Apache Image') {
                    steps {
                        sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ."
                        echo "Apache image built: ${DOCKER_WEB_IMAGE}"
                    }
                }
                stage('Build MySQL Image') {
                    steps {
                        sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ."
                        echo "MySQL image built: ${DOCKER_DB_IMAGE}"
                    }
                }
            }
        }

        stage('Deploy Using Docker Compose') {
            steps {
                sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml up -d"
                echo "Services started using docker-compose."
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

        stage('Rollback') {
            when {
                expression { env.DEPLOY_STATUS == 'bad' }
            }
            steps {
                sh "${CONTAINER_FILES_PATH}/rollback.sh"
                echo "Rollback executed."
            }
        }

        stage('Log Results to GitHub') {
            steps {
                script {
                    sh """
                    mkdir -p ${LOG_FOLDER}
                    echo 'Pipeline execution log' > ${LOG_FOLDER}/log.txt
                    git config --global user.email "you@example.com"
                    git config --global user.name "Your Name"
                    git add ${LOG_FOLDER}
                    git commit -m 'Pipeline logs updated'
                    git push https://${GIT_USERNAME}:${GIT_CREDENTIALS}@${GIT_REPO}
                    """
                    echo "Logs uploaded to GitHub."
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs in GitHub.'
        }
    }
}



pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'FYPtesting'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        GIT_CREDENTIALS = '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'
        GIT_USERNAME = 'githubadmin'
        LOG_FOLDER = 'pipeline-logs'
        CONTAINER_FILES_PATH = '/home/fypuser/fyp/Jingyi/container-files' // Full path to container files
    }

    triggers {
        pollSCM('* * * * *') // Polling every minute
    }

    stages {
        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main',
                        credentialsId: '570f5130-98ad-4f82-ac6b-4ec94f041e3e',
                        url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('Build and Test Containers') {
            parallel {
                stage('Build Apache Image') {
                    steps {
                        sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                        echo "Apache image built: ${DOCKER_WEB_IMAGE}"
                    }
                }
                stage('Build MySQL Image') {
                    steps {
                        sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                        echo "MySQL image built: ${DOCKER_DB_IMAGE}"
                    }
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
                    echo "Deploying production containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml up -d"
                }
            }
        }

        stage('Rollback') {
            when {
                expression { env.DEPLOY_STATUS == 'bad' }
            }
            steps {
                sh "${CONTAINER_FILES_PATH}/rollback.sh"
                echo "Rollback executed."
            }
        }

        stage('Log Results to GitHub') {
            steps {
                script {
                    sh """
                    mkdir -p ${LOG_FOLDER}
                    echo 'Pipeline execution log' > ${LOG_FOLDER}/log.txt
                    git config --global user.email "you@example.com"
                    git config --global user.name "Your Name"
                    git add ${LOG_FOLDER}
                    git commit -m 'Pipeline logs updated'
                    git push https://${GIT_USERNAME}:${GIT_CREDENTIALS}@${GIT_REPO}
                    """
                    echo "Logs uploaded to GitHub."
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs in GitHub.'
        }
    }
}

