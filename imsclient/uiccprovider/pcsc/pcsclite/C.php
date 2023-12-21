<?php

namespace imsclient\uiccprovider\pcsc\pcsclite;

abstract class C
{
    const MAX_ATR_SIZE = 33;
    const MAX_BUFFER_SIZE = 264;
    const MAX_READERNAME = 128;

    const SCARD_SCOPE_SYSTEM = 0x0002;

    const SCARD_AUTOALLOCATE = -1;

    const SCARD_SHARE_SHARED = 0x0002;

    const SCARD_PROTOCOL_T0 = 0x0001;
    const SCARD_PROTOCOL_T1 = 0x0002;

    const SCARD_LEAVE_CARD = 0x0000;

    const TYPES_DEF = 'typedef unsigned char BYTE;
typedef unsigned char UCHAR;
typedef UCHAR *PUCHAR;
typedef unsigned short USHORT;
typedef unsigned long ULONG;
typedef void *LPVOID;
typedef void *LPCVOID;
typedef unsigned long DWORD;
typedef DWORD *PDWORD;
typedef long LONG;
typedef char *LPCSTR;
typedef BYTE *LPCBYTE;
typedef BYTE *LPBYTE;
typedef DWORD *LPDWORD;
typedef char *LPSTR;
typedef LPSTR LPTSTR;
typedef LPCSTR LPCTSTR;
typedef short BOOL;
typedef unsigned short WORD;
typedef ULONG *PULONG;
typedef LONG SCARDCONTEXT;
typedef SCARDCONTEXT *PSCARDCONTEXT;
typedef SCARDCONTEXT *LPSCARDCONTEXT;
typedef LONG SCARDHANDLE;
typedef SCARDHANDLE *PSCARDHANDLE;
typedef SCARDHANDLE *LPSCARDHANDLE;
typedef struct {
  char *szReader;
  void *pvUserData;
  DWORD dwCurrentState;
  DWORD dwEventState;
  DWORD cbAtr;
  unsigned char rgbAtr[33];
} SCARD_READERSTATE;
typedef struct {
  char *szReader;
  void *pvUserData;
  DWORD dwCurrentState;
  DWORD dwEventState;
  DWORD cbAtr;
  unsigned char rgbAtr[33];
} *LPSCARD_READERSTATE;
typedef struct {
  unsigned long dwProtocol;
  unsigned long cbPciLength;
} SCARD_IO_REQUEST;
typedef struct {
  unsigned long dwProtocol;
  unsigned long cbPciLength;
} *PSCARD_IO_REQUEST;
typedef struct {
  unsigned long dwProtocol;
  unsigned long cbPciLength;
} *LPSCARD_IO_REQUEST;
typedef SCARD_IO_REQUEST *LPCSCARD_IO_REQUEST;
';
    const HEADER_DEF = self::TYPES_DEF . 'extern SCARD_IO_REQUEST g_rgSCardT0Pci;
extern SCARD_IO_REQUEST g_rgSCardT1Pci;
extern SCARD_IO_REQUEST g_rgSCardRawPci;
char *pcsc_stringify_error(LONG);
LONG SCardEstablishContext(DWORD dwScope, LPCVOID pvReServed1, LPCVOID pvReServed2, LPSCARDCONTEXT phContext);
LONG SCardReleaseContext(SCARDCONTEXT hContext);
LONG SCardIsValidContext(SCARDCONTEXT hContext);
LONG SCardConnect(SCARDCONTEXT hContext, LPCSTR szReader, DWORD dwShareMode, DWORD dwPreferredProtocols, LPSCARDHANDLE phCard, LPDWORD pdwActiveProtocol);
LONG SCardReconnect(SCARDHANDLE hCard, DWORD dwShareMode, DWORD dwPreferredProtocols, DWORD dwInitialization, LPDWORD pdwActiveProtocol);
LONG SCardDisconnect(SCARDHANDLE hCard, DWORD dwDisposition);
LONG SCardBeginTransaction(SCARDHANDLE hCard);
LONG SCardEndTransaction(SCARDHANDLE hCard, DWORD dwDisposition);
LONG SCardStatus(SCARDHANDLE hCard, LPSTR mszReaderName, LPDWORD pcchReaderLen, LPDWORD pdwState, LPDWORD pdwProtocol, LPBYTE pbAtr, LPDWORD pcbAtrLen);
LONG SCardGetStatusChange(SCARDCONTEXT hContext, DWORD dwTimeout, SCARD_READERSTATE *rgReaderStates, DWORD cReaders);
LONG SCardControl(SCARDHANDLE hCard, DWORD dwControlCode, LPCVOID pbSendBuffer, DWORD cbSendLength, LPVOID pbRecvBuffer, DWORD cbRecvLength, LPDWORD lpBytesReturned);
LONG SCardTransmit(SCARDHANDLE hCard, SCARD_IO_REQUEST *pioSendPci, LPCBYTE pbSendBuffer, DWORD cbSendLength, SCARD_IO_REQUEST *pioRecvPci, LPBYTE pbRecvBuffer, LPDWORD pcbRecvLength);
LONG SCardListReaderGroups(SCARDCONTEXT hContext, LPSTR mszGroups, LPDWORD pcchGroups);
LONG SCardListReaders(SCARDCONTEXT hContext, LPCSTR mszGroups, LPSTR mszReaders, LPDWORD pcchReaders);
LONG SCardFreeMemory(SCARDCONTEXT hContext, LPCVOID pvMem);
LONG SCardCancel(SCARDCONTEXT hContext);
LONG SCardGetAttrib(SCARDHANDLE hCard, DWORD dwAttrId, LPBYTE pbAttr, LPDWORD pcbAttrLen);
LONG SCardSetAttrib(SCARDHANDLE hCard, DWORD dwAttrId, LPCBYTE pbAttr, DWORD cbAttrLen);
';
}
