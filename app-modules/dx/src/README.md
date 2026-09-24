# DX

Stand-ins that let the whole application run with no stack on the network. The
root manifest requires this module under `require-dev`, so a release does not
contain it.

`DxServiceProvider` binds nothing unless the `dx.stands_in` setting is on. When
it is, these `StandsIn` implementations take the place of real ones:

| | |
|---|---|
| `AStackThatIsNotThere` | A stack that answers every request and is not running |
| `ADoorThatIsNotThere` | A stack that a password can be offered to |
| `ADeviceAlreadyPaired`, `ASessionThisRunKeeps`, `VerdictsThisRunKeeps` | A device that is already paired, keeping what it holds for one run |
| `ACameraThatSeesAStandIn` | A camera that always reads a pairing code for a new stack |

`ClientsThatReachNothing` and `DoorsThatOpenOnNothing` wrap the real SDK
client and door with a transport that answers from the installed SDK's own
contract, so the SDK's envelope reading and version check still run.
