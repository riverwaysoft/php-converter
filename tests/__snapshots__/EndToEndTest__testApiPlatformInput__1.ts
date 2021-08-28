export enum ColorEnum {
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
  location: { lat: string; lan: string };
};
