pipeline{
    agent any
    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        disableConcurrentBuilds()
    }
    parameters {
        string(name: 'IMAGE_REPO_NAME', defaultValue: 'example.gitlab.com/phpapiboilerplate', description: 'The base image name')
        string(name: 'LATEST_BUILD_TAG', defaultValue: 'latest', description: 'The tag for the latest version')
        booleanParam(name: 'PUSH_DOCKER_IMAGES', defaultValue: true, description: 'Set to false to disable image pushes')
    }
    environment {
        REGISTRY_CREDS = credentials('user-registry-credentials')
        BRANCH_NAME = sh(returnStdout: true, script: 'git rev-parse --abbrev-ref HEAD').trim()
        COMMIT_TAG = sh(returnStdout: true, script: 'git rev-parse HEAD').trim().take(7)
        BUILD_IMAGE_REPO_TAG = "${params.IMAGE_REPO_NAME}:${env.BUILD_TAG}"
        PKG_VERSION = "${readJSON(file: 'composer.json').version}"
    }
    stages{
        stage ("Start") {
            steps {
                slackSend (color: 'warning', message: "Job started ${env.JOB_NAME} $PKG_VERSION (<${env.BUILD_URL}|Open>)")
            }
        }
        stage('Build') {
            steps {
                sh "docker build -t $BUILD_IMAGE_REPO_TAG-base --target base ."
            }
            post{
                success{
                    echo "====++++Base image built++++===="
                }
            }
        }

        stage('Linting and Unit tests') {
            steps {
                sh "docker build -t $BUILD_IMAGE_REPO_TAG-test --target test ."
            }
            post{
                success{
                    echo "====++++Tests passed++++===="
                }
            }
        }
        
        stage('Integration tests') {
            environment {
                MYSQL_ROOT_PASSWORD = "root"
                MYSQL_DATABASE = "phpboilerplate"
                MYSQL_USER = "user"
                MYSQL_PASSWORD = "password"
            }
            steps {
                sh '''
                    docker run --name testDb \
                    -e $MYSQL_ROOT_PASSWORD \
                    -e $MYSQL_DATABASE \
                    -e $MYSQL_USER \
                    -e $MYSQL_PASSWORD \
                    -d mariadb
                    '''
                sh '''
                docker run --link=testDb:database --rm \
                    -e DB_USER=$MYSQL_USER \
                    -e DB_PASSWORD=$MYSQL_PASSWORD \
                    -e DB_DATABASE=$MYSQL_DATABASE \
                    -u node \
                    $BUILD_IMAGE_REPO_TAG-test
                    '''
            }
            post{
                always{
                    sh "docker rm --force testDb"
                }
                success{
                    echo "====++++Tests passed++++===="
                }
                failure{
                    echo "====++++Tests failed++++===="
                }
            }
        }

        stage('Audit and scans') {
            steps {
                sh 'docker build -t $BUILD_IMAGE_REPO_TAG-audit --target audit .'
            }
            post{
                success{
                    echo "====++++Scans OK++++===="
                }
            }
        }

        stage('Build production image') {
            steps {
                sh('docker build -t $BUILD_IMAGE_REPO_TAG .')
                sh('docker tag $BUILD_IMAGE_REPO_TAG ${IMAGE_REPO_NAME}:$COMMIT_TAG')
                sh('docker tag $BUILD_IMAGE_REPO_TAG ${IMAGE_REPO_NAME}:$PKG_VERSION')
                sh('docker tag $BUILD_IMAGE_REPO_TAG ${IMAGE_REPO_NAME}:${LATEST_BUILD_TAG}')
                sh('docker tag $BUILD_IMAGE_REPO_TAG ${IMAGE_REPO_NAME}:$BRANCH_NAME-latest')
            }
            post{
                success{
                    echo "====++++Built++++===="
                }
            }
        }

        stage('Push') {
            environment {
                REGISTRY_NAME = 'example.gitlab.com'
            }
            when {
                expression {
                    return params.PUSH_DOCKER_IMAGES
                }
            }
            steps {
                sh('docker login -u $REGISTRY_CREDS_USR -p $REGISTRY_CREDS_PSW $REGISTRY_NAME')
                // Production image
                sh('docker push $BUILD_IMAGE_REPO_TAG')
                sh('docker push ${IMAGE_REPO_NAME}:$COMMIT_TAG')
                sh('docker push ${IMAGE_REPO_NAME}:$PKG_VERSION')
                sh('docker push ${IMAGE_REPO_NAME}:${LATEST_BUILD_TAG}')
                sh('docker push ${IMAGE_REPO_NAME}:$BRANCH_NAME-latest')
            }
            post{
                always {
                    sh "docker logout $REGISTRY_NAME"
                }
                success{
                    echo "====++++Pushed++++===="
                }
            }
        }

        stage ("Cleaning") {
            when {
                expression {
                     env.BUILD_NUMBER.toBigInteger().mod(5) == 0
                }
            }
            steps {
                echo "Cleaning workspace"
                deleteDir()
            }
        }
    }
    post{
        always{
            echo "====++++Job done++++===="
        }
        success{
            slackSend (color: 'good', message: "Job done ${env.JOB_NAME} $PKG_VERSION (<${env.BUILD_URL}|Open>)")
        }
        failure{
            slackSend (color: 'danger', message: "Job failed ${env.JOB_NAME} $PKG_VERSION (<${env.BUILD_URL}|Open>)")
        }
    }
}