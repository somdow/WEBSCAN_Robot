# Containerization Validation Checklist

## Architecture
- [ ] All services identified
- [ ] Dependencies mapped
- [ ] Networking designed
- [ ] Stateful components addressed

## Dockerfiles
- [ ] Multi-stage build used
- [ ] Non-root user configured
- [ ] Health check defined
- [ ] .dockerignore created
- [ ] Minimal base image selected
- [ ] Layers optimized

## Docker Compose
- [ ] All services defined
- [ ] Volumes configured
- [ ] Environment variables handled
- [ ] Networks configured
- [ ] Works locally

## Kubernetes (if applicable)
- [ ] Deployments created
- [ ] Services defined
- [ ] ConfigMaps/Secrets separated
- [ ] Resource limits set
- [ ] Liveness/readiness probes
- [ ] HPA configured

## Security
- [ ] No secrets in images
- [ ] Vulnerability scanning mentioned
- [ ] Non-root execution
- [ ] Resource limits defined
- [ ] Network policies considered

## Documentation
- [ ] Local dev instructions
- [ ] Build instructions
- [ ] Deployment instructions
- [ ] Debugging guide
