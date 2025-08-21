```mermaid
sequenceDiagram
    Departamento->>+Sist. de Vinculación Temp: Registra solicitud (Form 45 digital)
    Sist. de Vinculación Temp->>+Banco de aspirantes: Valida perfil docente 
    Banco de aspirantes-->>-Sistema: Confirma elegibilidad
    Sist. de Vinculación Temp->>+Facultad: Notifica nueva solicitud
    Facultad->>+Sistema: Aprueba/Rechaza con justificación
    Sist. de Vinculación Temp->>+VRA: Envía consolidado facultad
    VRA->>+Sistema: Valida viabilidad global
    Sist. de Vinculación Temp->>+DGTH: Remite solicitudes validadas
    DGTH-->>-Sist. de Vinculación: Confirma recepción
    Sist. de Vinculación Temp-->>-Departamento: Notifica estado final