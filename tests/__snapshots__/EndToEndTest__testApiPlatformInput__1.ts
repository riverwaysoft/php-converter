export enum ColorEnum {
  UNKNOWN = null,
  RED = 0,
  GREEN = 1,
  BLUE = 2,
}

export type ProfileOutput = {
  firstName: string;
  lastName: string;
};

export type UserCreateInput = {
  profile: string;
  promotedAt: string | null;
  userTheme: { value: ColorEnum };
  industriesUnion: string[] | null;
  industriesNullable: string[] | null;
  money: { currency: string; amount: number };
  location: { lat: string; lan: string };
};
