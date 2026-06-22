<?php

namespace App\Modules\Billing\Services;

use Exception;
use DOMDocument;

class XmlSignerService
{
    /**
     * Sign an XML string using XAdES-BES and a .p12 certificate file.
     *
     * @param string $xmlContent The raw XML content.
     * @param string $p12Content The binary content of the .p12 file.
     * @param string $password The password of the certificate.
     * @return string
     * @throws Exception
     */
    public function signXml(string $xmlContent, string $p12Content, string $password): string
    {
        $certs = app(CertificateHelper::class)->parsePkcs12($p12Content, $password);

        $privateKey = $certs['pkey'];
        $publicKeyCert = $certs['cert'];

        // Clean cert string to extract base64 representation
        $certClean = $this->cleanCertificateString($publicKeyCert);

        // Load XML as DOM
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        if (!$doc->loadXML($xmlContent)) {
            throw new Exception("No se pudo cargar la estructura XML para su firma.");
        }

        // Generate IDs and dynamic numbers
        $signId = "Signature-" . rand(100000, 999999);
        $signedInfoId = "Signature-Info-" . rand(100000, 999999);
        $signedPropsId = "Signed-Properties-" . rand(100000, 999999);
        $keyInfoId = "Key-Info-" . rand(100000, 999999);
        $refId = "Reference-ID-" . rand(100000, 999999);

        // Standard canonicalization method
        $c14nMethod = "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";

        // Get digest of the main XML element
        $rootNode = $doc->documentElement;
        $xmlCanonical = $rootNode->C14N(false, false);
        $xmlDigest = base64_encode(hash('sha256', $xmlCanonical, true));

        // Get certificate details for SignedProperties
        $certData = openssl_x509_parse($publicKeyCert);
        $issuerName = $this->getIssuerString($certData['issuer'] ?? []);
        $serialNumber = $certData['serialNumber'] ?? '0';

        // Calculate certificate digest from binary DER representation
        $derContent = base64_decode($certClean);
        $certDigestValue = base64_encode(hash('sha256', $derContent, true));

        // 1. Build XAdES SignedProperties XML block
        $signingTime = date('Y-m-d\TH:i:sP');
        $signedPropertiesXml = 
            '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="' . $signedPropsId . '">' .
                '<xades:SignedSignatureProperties>' .
                    '<xades:SigningTime>' . $signingTime . '</xades:SigningTime>' .
                    '<xades:SigningCertificate>' .
                        '<xades:Cert>' .
                            '<xades:CertDigest>' .
                                '<ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' .
                                '<ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $certDigestValue . '</ds:DigestValue>' .
                            '</xades:CertDigest>' .
                            '<xades:IssuerSerial>' .
                                '<ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . htmlspecialchars($issuerName) . '</ds:X509IssuerName>' .
                                '<ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $serialNumber . '</ds:X509SerialNumber>' .
                            '</xades:IssuerSerial>' .
                        '</xades:Cert>' .
                    '</xades:SigningCertificate>' .
                '</xades:SignedSignatureProperties>' .
            '</xades:SignedProperties>';

        $signedPropsDummyXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"><ds:Object><xades:QualifyingProperties Target="#' . $signId . '">' . $signedPropertiesXml . '</xades:QualifyingProperties></ds:Object></ds:Signature>';
        $signedPropsDom = new DOMDocument();
        $signedPropsDom->preserveWhiteSpace = false;
        $signedPropsDom->loadXML($signedPropsDummyXml);
        $signedPropsNode = $signedPropsDom->getElementsByTagNameNS('http://uri.etsi.org/01903/v1.3.2#', 'SignedProperties')->item(0);
        $signedPropsCanonical = $signedPropsNode->C14N(false, false);
        $signedPropsDigest = base64_encode(hash('sha256', $signedPropsCanonical, true));

        // 2. Build KeyInfo structure
        $keyInfoXml = 
            '<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="' . $keyInfoId . '">' .
                '<ds:X509Data>' .
                    '<ds:X509Certificate>' . $certClean . '</ds:X509Certificate>' .
                '</ds:X509Data>' .
            '</ds:KeyInfo>';

        $keyInfoDummyXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">' . $keyInfoXml . '</ds:Signature>';
        $keyInfoDom = new DOMDocument();
        $keyInfoDom->preserveWhiteSpace = false;
        $keyInfoDom->loadXML($keyInfoDummyXml);
        $keyInfoNode = $keyInfoDom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'KeyInfo')->item(0);
        $keyInfoCanonical = $keyInfoNode->C14N(false, false);
        $keyInfoDigest = base64_encode(hash('sha256', $keyInfoCanonical, true));

        // 3. Build SignedInfo block
        $signedInfoXml = 
            '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="' . $signedInfoId . '">' .
                '<ds:CanonicalizationMethod Algorithm="' . $c14nMethod . '"/>' .
                '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>' .
                '<ds:Reference Id="' . $refId . '" URI="#comprobante">' .
                    '<ds:Transforms>' .
                        '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>' .
                    '</ds:Transforms>' .
                    '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' .
                    '<ds:DigestValue>' . $xmlDigest . '</ds:DigestValue>' .
                '</ds:Reference>' .
                '<ds:Reference URI="#' . $keyInfoId . '">' .
                    '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' .
                    '<ds:DigestValue>' . $keyInfoDigest . '</ds:DigestValue>' .
                '</ds:Reference>' .
                '<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#' . $signedPropsId . '">' .
                    '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' .
                    '<ds:DigestValue>' . $signedPropsDigest . '</ds:DigestValue>' .
                '</ds:Reference>' .
            '</ds:SignedInfo>';

        $signedInfoDummyXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">' . $signedInfoXml . '</ds:Signature>';
        $signedInfoDom = new DOMDocument();
        $signedInfoDom->preserveWhiteSpace = false;
        $signedInfoDom->loadXML($signedInfoDummyXml);
        $signedInfoNode = $signedInfoDom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'SignedInfo')->item(0);
        $signedInfoCanonical = $signedInfoNode->C14N(false, false);

        // 4. Cryptographically sign the SignedInfo block
        $signatureValue = '';
        if (!openssl_sign($signedInfoCanonical, $signatureValue, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Error al generar la firma digital del XML.");
        }
        $signatureValueBase64 = base64_encode($signatureValue);

        // 5. Build final Signature block
        $signatureBlockXml = 
            '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="' . $signId . '">' .
                $signedInfoXml .
                '<ds:SignatureValue>' . $signatureValueBase64 . '</ds:SignatureValue>' .
                $keyInfoXml .
                '<ds:Object>' .
                    '<xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Target="#' . $signId . '">' .
                        $signedPropertiesXml .
                    '</xades:QualifyingProperties>' .
                '</ds:Object>' .
            '</ds:Signature>';

        $signatureBlockDom = new DOMDocument();
        $signatureBlockDom->loadXML($signatureBlockXml);
        $signatureNode = $signatureBlockDom->documentElement;

        // Import signature block to main XML
        $importedSignature = $doc->importNode($signatureNode, true);
        $doc->documentElement->appendChild($importedSignature);

        return $doc->saveXML();
    }

    /**
     * Helper to strip cert headers and spaces for base64 blocks.
     */
    private function cleanCertificateString(string $cert): string
    {
        $cert = str_replace("-----BEGIN CERTIFICATE-----", "", $cert);
        $cert = str_replace("-----END CERTIFICATE-----", "", $cert);
        $cert = str_replace(array("\r", "\n", " "), "", $cert);
        return $cert;
    }

    /**
     * Helper to stringify certificate issuer names.
     */
    private function getIssuerString(array $issuer): string
    {
        $parts = [];
        foreach ($issuer as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $parts[] = "$key=$value";
        }
        return implode(', ', array_reverse($parts));
    }
}
