"""
Ituran API Integration Service
Handles both MobileService and Service3 APIs with fallback logic
"""
import logging
from datetime import datetime, timedelta
from typing import Optional, Dict, Any, List
import requests
from zeep import Client, Settings
from zeep.exceptions import Fault
from config import Config

logger = logging.getLogger(__name__)


class IturanAPIError(Exception):
    """Custom exception for Ituran API errors"""
    pass


class IturanService:
    """
    Ituran API integration with intelligent fallback
    Priority: GetDailyVehicleDistance -> GetFullReport (with chunks)
    """

    def __init__(self):
        self.username = Config.ITURAN_USERNAME
        self.password = Config.ITURAN_PASSWORD
        self.mobile_url = Config.ITURAN_MOBILE_URL
        self.service3_url = Config.ITURAN_SERVICE3_URL

        # SOAP clients with timeout settings
        settings = Settings(strict=False, xml_huge_tree=True)
        self.mobile_client = None
        self.service3_client = None

        try:
            self.mobile_client = Client(self.mobile_url, settings=settings)
            logger.info("✅ MobileService SOAP client initialized")
        except Exception as e:
            logger.error(f"❌ Failed to initialize MobileService client: {e}")

        try:
            self.service3_client = Client(self.service3_url, settings=settings)
            logger.info("✅ Service3 SOAP client initialized")
        except Exception as e:
            logger.error(f"❌ Failed to initialize Service3 client: {e}")

    def get_daily_km(
        self,
        plate: str,
        date: datetime,
        area_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Get daily mileage with intelligent fallback

        Priority:
        1. GetDailyVehicleDistance (MobileService) - Fast, direct KM
        2. GetFullReport (Service3) - Slower, requires calculation

        Args:
            plate: Vehicle plate
            date: Date to calculate
            area_id: Optional area/geo_entity_id filter

        Returns:
            Dict with km_driven, start_odometer, end_odometer, method, etc.
        """
        logger.info(f"📊 Calculating KM for {plate} on {date.date()}")

        # Try primary method: GetDailyVehicleDistance
        try:
            result = self._get_daily_km_mobile_api(plate, date)
            if result['success']:
                logger.info(f"✅ {plate}: {result['km_driven']} km (MobileAPI)")
                return result
        except Exception as e:
            logger.warning(f"⚠️ MobileAPI failed for {plate}: {e}")

        # Fallback: GetFullReport with odometer calculation
        try:
            result = self._get_daily_km_full_report(plate, date, area_id)
            if result['success']:
                logger.info(
                    f"✅ {plate}: {result['km_driven']} km (FullReport fallback)"
                )
                return result
        except Exception as e:
            logger.error(f"❌ FullReport also failed for {plate}: {e}")
            return {
                'success': False,
                'km_driven': 0,
                'error': str(e),
                'method': 'failed'
            }

        return {
            'success': False,
            'km_driven': 0,
            'error': 'All methods failed',
            'method': 'failed'
        }

    def _get_daily_km_mobile_api(
        self,
        plate: str,
        date: datetime
    ) -> Dict[str, Any]:
        """
        Get daily KM using GetDailyVehicleDistance (MobileService)
        This is the PREFERRED method - returns KM directly
        """
        if not self.mobile_client:
            raise IturanAPIError("MobileService client not initialized")

        try:
            # Format date for API (YYYY-MM-DD)
            date_str = date.strftime('%Y-%m-%d')

            logger.debug(f"📡 Calling GetDailyVehicleDistance for {plate} on {date_str}")

            response = self.mobile_client.service.GetDailyVehicleDistance(
                UserName=self.username,
                Password=self.password,
                Plate=plate,
                Date=date_str
            )

            # Parse response
            if hasattr(response, 'Success') and response.Success:
                km_driven = float(response.Distance) if hasattr(response, 'Distance') else 0.0

                return {
                    'success': True,
                    'km_driven': round(km_driven, 2),
                    'start_odometer': None,  # Not provided by this API
                    'end_odometer': None,
                    'method': 'mobile_api',
                    'data_source': 'GetDailyVehicleDistance',
                    'record_count': 1,
                    'error': None
                }
            else:
                error_msg = response.ErrorMessage if hasattr(response, 'ErrorMessage') else 'Unknown error'
                raise IturanAPIError(f"MobileAPI returned error: {error_msg}")

        except Fault as e:
            logger.error(f"SOAP Fault in GetDailyVehicleDistance: {e}")
            raise IturanAPIError(f"SOAP error: {str(e)}")
        except Exception as e:
            logger.error(f"Error in GetDailyVehicleDistance: {e}")
            raise

    def _get_daily_km_full_report(
        self,
        plate: str,
        date: datetime,
        area_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Get daily KM using GetFullReport (Service3) - FALLBACK method
        Calculates KM from odometer difference (first - last record)
        Handles chunking for API 3-day limit
        """
        if not self.service3_client:
            raise IturanAPIError("Service3 client not initialized")

        # Define time range for the day (00:00 to 23:59)
        start_datetime = date.replace(hour=0, minute=0, second=0, microsecond=0)
        end_datetime = date.replace(hour=23, minute=59, second=59, microsecond=0)

        try:
            # Check if period needs chunking (>3 days)
            days_diff = (end_datetime - start_datetime).days

            if days_diff > 3:
                logger.info(f"Period > 3 days, chunking required for {plate}")
                return self._get_km_with_chunking(
                    plate, start_datetime, end_datetime, area_id
                )

            # Single request for <=3 days
            return self._fetch_full_report(
                plate, start_datetime, end_datetime, area_id
            )

        except Exception as e:
            logger.error(f"Error in GetFullReport: {e}")
            raise

    def _fetch_full_report(
        self,
        plate: str,
        start_dt: datetime,
        end_dt: datetime,
        area_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Fetch full report from Service3 API
        """
        try:
            logger.debug(
                f"📡 Calling GetFullReport for {plate} "
                f"({start_dt} to {end_dt})"
            )

            # Choose method based on area_id
            if area_id:
                response = self.service3_client.service.GetFullReportWithFilters(
                    UserName=self.username,
                    Password=self.password,
                    Plate=plate,
                    Start=start_dt.isoformat(),
                    End=end_dt.isoformat(),
                    UAID=0,
                    GeoEntityIds=[area_id],
                    MaxNumberOfRecords=10000
                )
            else:
                response = self.service3_client.service.GetFullReport(
                    UserName=self.username,
                    Password=self.password,
                    Plate=plate,
                    Start=start_dt.isoformat(),
                    End=end_dt.isoformat(),
                    UAID=0,
                    MaxNumberOfRecords=10000
                )

            # Parse XML response
            records = self._parse_full_report_xml(response)

            if not records:
                return {
                    'success': False,
                    'km_driven': 0,
                    'error': 'No records found',
                    'method': 'full_report',
                    'record_count': 0
                }

            # Calculate KM from odometer difference
            result = self._calculate_km_from_records(records, plate)
            result['method'] = 'full_report'
            result['data_source'] = 'GetFullReport' + ('WithFilters' if area_id else '')

            return result

        except Fault as e:
            logger.error(f"SOAP Fault in GetFullReport: {e}")
            raise IturanAPIError(f"SOAP error: {str(e)}")
        except Exception as e:
            logger.error(f"Error in GetFullReport: {e}")
            raise

    def _get_km_with_chunking(
        self,
        plate: str,
        start_dt: datetime,
        end_dt: datetime,
        area_id: Optional[int] = None
    ) -> Dict[str, Any]:
        """
        Handle periods >3 days by chunking requests
        """
        chunks = []
        current = start_dt

        while current < end_dt:
            chunk_end = min(current + timedelta(days=3), end_dt)
            chunks.append((current, chunk_end))
            current = chunk_end

        logger.info(f"Splitting into {len(chunks)} chunks for {plate}")

        all_records = []

        for i, (chunk_start, chunk_end) in enumerate(chunks):
            try:
                logger.debug(f"  Chunk {i+1}/{len(chunks)}: {chunk_start} to {chunk_end}")

                result = self._fetch_full_report(
                    plate, chunk_start, chunk_end, area_id
                )

                if result['success'] and 'records' in result:
                    all_records.extend(result.get('records', []))

            except Exception as e:
                logger.warning(f"Chunk {i+1} failed: {e}")
                continue

        if not all_records:
            return {
                'success': False,
                'km_driven': 0,
                'error': 'No records in any chunk',
                'method': 'full_report_chunked',
                'record_count': 0
            }

        # Calculate from all records
        result = self._calculate_km_from_records(all_records, plate)
        result['method'] = 'full_report_chunked'
        result['data_source'] = 'GetFullReport_chunked'

        return result

    def _parse_full_report_xml(self, response: Any) -> List[Dict]:
        """Parse GetFullReport XML response"""
        records = []

        try:
            # Response is XML string or object
            if hasattr(response, 'root'):
                xml_records = response.root.findall('.//Record')
            else:
                import xml.etree.ElementTree as ET
                root = ET.fromstring(str(response))
                xml_records = root.findall('.//Record')

            for record in xml_records:
                parsed = {}
                for child in record:
                    parsed[child.tag] = child.text
                records.append(parsed)

        except Exception as e:
            logger.error(f"Error parsing XML: {e}")

        return records

    def _calculate_km_from_records(
        self,
        records: List[Dict],
        plate: str
    ) -> Dict[str, Any]:
        """
        Calculate KM from odometer readings
        Handles:
        - Meters vs KM conversion (>1,000,000 = meters)
        - Zero odometers (find valid values in middle)
        - Negative KM (data error, return 0)
        """
        if not records:
            return {
                'success': False,
                'km_driven': 0,
                'error': 'No records',
                'record_count': 0
            }

        # Get first and last mileage
        first_mileage = float(records[0].get('Mileage', 0) or 0)
        last_mileage = float(records[-1].get('Mileage', 0) or 0)

        # Normalize to KM (if >1,000,000 it's in meters)
        start_odo = first_mileage / 1000 if first_mileage >= 1_000_000 else first_mileage
        end_odo = last_mileage / 1000 if last_mileage >= 1_000_000 else last_mileage

        # Handle zero odometers - find valid values
        if start_odo == 0 and end_odo == 0 and len(records) > 2:
            logger.warning(f"{plate}: Both odometers zero, searching for valid values")

            # Find first valid
            for record in records:
                mileage = float(record.get('Mileage', 0) or 0)
                odo = mileage / 1000 if mileage >= 1_000_000 else mileage
                if odo > 0:
                    start_odo = odo
                    break

            # Find last valid
            for record in reversed(records):
                mileage = float(record.get('Mileage', 0) or 0)
                odo = mileage / 1000 if mileage >= 1_000_000 else mileage
                if odo > 0:
                    end_odo = odo
                    break

        # Calculate KM driven
        km_driven = end_odo - start_odo

        # Handle negative KM (data error)
        if km_driven < 0:
            logger.warning(
                f"{plate}: Negative KM detected "
                f"({start_odo} -> {end_odo}), returning 0"
            )
            km_driven = 0

        return {
            'success': True,
            'km_driven': round(km_driven, 2),
            'start_odometer': round(start_odo, 2),
            'end_odometer': round(end_odo, 2),
            'record_count': len(records),
            'error': None,
            'records': records  # Keep for chunking
        }


# Singleton instance
ituran_service = IturanService()
