"""
Fleet Management - Database Models
"""
from datetime import datetime
from flask_sqlalchemy import SQLAlchemy
from sqlalchemy import Index

db = SQLAlchemy()


class Vehicle(db.Model):
    """Vehicle model"""
    __tablename__ = 'vehicles'

    id = db.Column(db.Integer, primary_key=True)
    plate = db.Column(db.String(20), unique=True, nullable=False, index=True)
    brand = db.Column(db.String(100))
    model = db.Column(db.String(100))
    year = db.Column(db.Integer)
    area_id = db.Column(db.Integer, db.ForeignKey('areas.id'), nullable=True)
    is_active = db.Column(db.Boolean, default=True, index=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    area = db.relationship('Area', back_populates='vehicles')
    daily_mileages = db.relationship(
        'DailyMileage',
        back_populates='vehicle',
        cascade='all, delete-orphan'
    )

    def __repr__(self):
        return f'<Vehicle {self.plate}>'

    def to_dict(self):
        return {
            'id': self.id,
            'plate': self.plate,
            'brand': self.brand,
            'model': self.model,
            'year': self.year,
            'area_id': self.area_id,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }


class Area(db.Model):
    """Geographic area/region model"""
    __tablename__ = 'areas'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    geo_entity_id = db.Column(db.Integer, nullable=True)  # Ituran GeoEntityId
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    # Relationships
    vehicles = db.relationship('Vehicle', back_populates='area')

    def __repr__(self):
        return f'<Area {self.name}>'

    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'geo_entity_id': self.geo_entity_id,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class DailyMileage(db.Model):
    """Daily mileage records"""
    __tablename__ = 'daily_mileage'

    id = db.Column(db.Integer, primary_key=True)
    vehicle_id = db.Column(db.Integer, db.ForeignKey('vehicles.id'), nullable=False)
    date = db.Column(db.Date, nullable=False, index=True)
    km_driven = db.Column(db.Float, nullable=False, default=0.0)
    start_odometer = db.Column(db.Float, nullable=True)
    end_odometer = db.Column(db.Float, nullable=True)
    calculation_method = db.Column(
        db.String(50),
        nullable=False,
        default='mobile_api'
    )  # 'mobile_api' or 'full_report'
    data_source = db.Column(db.String(50), nullable=True)  # API endpoint used
    record_count = db.Column(db.Integer, default=0)  # Number of GPS records
    calculation_status = db.Column(
        db.String(20),
        default='pending'
    )  # pending, success, error
    error_message = db.Column(db.Text, nullable=True)
    retry_count = db.Column(db.Integer, default=0)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    vehicle = db.relationship('Vehicle', back_populates='daily_mileages')

    # Composite index for efficient queries
    __table_args__ = (
        Index('ix_daily_mileage_vehicle_date', 'vehicle_id', 'date', unique=True),
        Index('ix_daily_mileage_status_date', 'calculation_status', 'date'),
    )

    def __repr__(self):
        return f'<DailyMileage vehicle={self.vehicle_id} date={self.date} km={self.km_driven}>'

    def to_dict(self):
        return {
            'id': self.id,
            'vehicle_id': self.vehicle_id,
            'vehicle_plate': self.vehicle.plate if self.vehicle else None,
            'date': self.date.isoformat(),
            'km_driven': self.km_driven,
            'start_odometer': self.start_odometer,
            'end_odometer': self.end_odometer,
            'calculation_method': self.calculation_method,
            'data_source': self.data_source,
            'record_count': self.record_count,
            'calculation_status': self.calculation_status,
            'error_message': self.error_message,
            'retry_count': self.retry_count,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }


class SyncLog(db.Model):
    """Synchronization execution logs"""
    __tablename__ = 'sync_logs'

    id = db.Column(db.Integer, primary_key=True)
    task_id = db.Column(db.String(100), nullable=True, index=True)
    task_name = db.Column(db.String(100), nullable=False)
    started_at = db.Column(db.DateTime, default=datetime.utcnow, index=True)
    finished_at = db.Column(db.DateTime, nullable=True)
    status = db.Column(db.String(20), default='running')  # running, success, failed
    vehicles_processed = db.Column(db.Integer, default=0)
    vehicles_success = db.Column(db.Integer, default=0)
    vehicles_failed = db.Column(db.Integer, default=0)
    error_message = db.Column(db.Text, nullable=True)

    def __repr__(self):
        return f'<SyncLog {self.task_name} at {self.started_at}>'

    def to_dict(self):
        duration = None
        if self.finished_at and self.started_at:
            duration = (self.finished_at - self.started_at).total_seconds()

        return {
            'id': self.id,
            'task_id': self.task_id,
            'task_name': self.task_name,
            'started_at': self.started_at.isoformat() if self.started_at else None,
            'finished_at': self.finished_at.isoformat() if self.finished_at else None,
            'duration_seconds': duration,
            'status': self.status,
            'vehicles_processed': self.vehicles_processed,
            'vehicles_success': self.vehicles_success,
            'vehicles_failed': self.vehicles_failed,
            'error_message': self.error_message
        }


class RouteCompliance(db.Model):
    """Route compliance check records - stores periodic analysis (every 5 min)"""
    __tablename__ = 'FF_RouteCompliance'

    id = db.Column(db.Integer, primary_key=True)
    route_id = db.Column(db.Integer, nullable=False, index=True)
    check_timestamp = db.Column(db.DateTime, nullable=False, index=True)
    vehicle_plate = db.Column(db.String(20), nullable=False)

    # Current vehicle position
    current_latitude = db.Column(db.Numeric(10, 8), nullable=False)
    current_longitude = db.Column(db.Numeric(11, 8), nullable=False)
    current_address = db.Column(db.String(500), nullable=True)
    current_speed = db.Column(db.Integer, default=0)

    # Compliance analysis
    expected_sequence_index = db.Column(db.Integer, nullable=True)
    distance_from_planned_route_km = db.Column(db.Numeric(8, 2), default=0)

    # Results
    is_compliant = db.Column(db.Boolean, default=True, index=True)
    compliance_score = db.Column(db.Numeric(5, 2), default=100.00)
    visits_completed = db.Column(db.Integer, default=0)
    visits_total = db.Column(db.Integer, default=0)

    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f'<RouteCompliance route={self.route_id} compliant={self.is_compliant}>'

    def to_dict(self):
        return {
            'id': self.id,
            'route_id': self.route_id,
            'check_timestamp': self.check_timestamp.isoformat() if self.check_timestamp else None,
            'vehicle_plate': self.vehicle_plate,
            'current_latitude': float(self.current_latitude) if self.current_latitude else None,
            'current_longitude': float(self.current_longitude) if self.current_longitude else None,
            'current_address': self.current_address,
            'current_speed': self.current_speed,
            'expected_sequence_index': self.expected_sequence_index,
            'distance_from_planned_route_km': float(self.distance_from_planned_route_km) if self.distance_from_planned_route_km else 0,
            'is_compliant': self.is_compliant,
            'compliance_score': float(self.compliance_score) if self.compliance_score else 100.0,
            'visits_completed': self.visits_completed,
            'visits_total': self.visits_total,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class RouteDeviation(db.Model):
    """Route deviations detected"""
    __tablename__ = 'FF_RouteDeviations'

    id = db.Column(db.Integer, primary_key=True)
    route_id = db.Column(db.Integer, nullable=False, index=True)
    compliance_check_id = db.Column(db.Integer, nullable=True)

    deviation_type = db.Column(
        db.Enum('wrong_sequence', 'excessive_distance', 'unplanned_stop',
                'skipped_location', 'route_abandoned', name='deviation_type_enum'),
        nullable=False,
        index=True
    )

    detected_at = db.Column(db.DateTime, nullable=False, index=True)
    location_latitude = db.Column(db.Numeric(10, 8), nullable=False)
    location_longitude = db.Column(db.Numeric(11, 8), nullable=False)
    location_address = db.Column(db.String(500), nullable=True)

    severity = db.Column(
        db.Enum('low', 'medium', 'high', 'critical', name='severity_enum'),
        default='medium',
        index=True
    )

    # Alert system
    alert_sent = db.Column(db.Boolean, default=False, index=True)
    alert_sent_at = db.Column(db.DateTime, nullable=True)
    alert_recipients = db.Column(db.Text, nullable=True)  # JSON with phone numbers

    # Resolution
    is_resolved = db.Column(db.Boolean, default=False)
    resolved_at = db.Column(db.DateTime, nullable=True)
    resolution_notes = db.Column(db.Text, nullable=True)

    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f'<RouteDeviation route={self.route_id} type={self.deviation_type} severity={self.severity}>'

    def to_dict(self):
        return {
            'id': self.id,
            'route_id': self.route_id,
            'compliance_check_id': self.compliance_check_id,
            'deviation_type': self.deviation_type,
            'detected_at': self.detected_at.isoformat() if self.detected_at else None,
            'location_latitude': float(self.location_latitude) if self.location_latitude else None,
            'location_longitude': float(self.location_longitude) if self.location_longitude else None,
            'location_address': self.location_address,
            'severity': self.severity,
            'alert_sent': self.alert_sent,
            'alert_sent_at': self.alert_sent_at.isoformat() if self.alert_sent_at else None,
            'alert_recipients': self.alert_recipients,
            'is_resolved': self.is_resolved,
            'resolved_at': self.resolved_at.isoformat() if self.resolved_at else None,
            'resolution_notes': self.resolution_notes,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class AlertRecipient(db.Model):
    """Alert recipients (directors, managers) configuration"""
    __tablename__ = 'FF_AlertRecipients'

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(255), nullable=False)
    role = db.Column(db.String(100), nullable=True)
    phone = db.Column(db.String(20), unique=True, nullable=False, index=True)
    email = db.Column(db.String(255), nullable=True)

    # Severity filters (which alerts to receive)
    receive_critical = db.Column(db.Boolean, default=True)
    receive_high = db.Column(db.Boolean, default=True)
    receive_medium = db.Column(db.Boolean, default=False)
    receive_low = db.Column(db.Boolean, default=False)

    # Schedule
    receive_weekdays = db.Column(db.Boolean, default=True)
    receive_weekends = db.Column(db.Boolean, default=False)
    start_hour = db.Column(db.Time, default=datetime.strptime('08:00', '%H:%M').time())
    end_hour = db.Column(db.Time, default=datetime.strptime('18:00', '%H:%M').time())

    is_active = db.Column(db.Boolean, default=True, index=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f'<AlertRecipient {self.name} ({self.phone})>'

    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'role': self.role,
            'phone': self.phone,
            'email': self.email,
            'receive_critical': self.receive_critical,
            'receive_high': self.receive_high,
            'receive_medium': self.receive_medium,
            'receive_low': self.receive_low,
            'receive_weekdays': self.receive_weekdays,
            'receive_weekends': self.receive_weekends,
            'start_hour': self.start_hour.isoformat() if self.start_hour else None,
            'end_hour': self.end_hour.isoformat() if self.end_hour else None,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }
